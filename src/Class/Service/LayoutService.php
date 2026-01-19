<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\CompactLayoutCollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CompactLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\TextLayoutPreviewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\LayoutCompact;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Text;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class LayoutService implements LayoutServiceInterface
{
    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private DataObjectServiceInterface $dataObjectService,
        private CompactLayoutHydratorInterface $compactLayoutHydrator,
        private CustomLayoutRepositoryInterface $customLayoutRepository,
        private SecurityServiceInterface $securityService,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAllLayoutsCollection(): array
    {
        $compactLayouts = [];
        $mapping = [];
        $customLayouts = $this->customLayoutRepository->getAllCustomLayouts();
        foreach ($customLayouts as $layout) {
            $mapping[$layout->getClassId()][] = $layout;
        }

        $classDefinitions = $this->classDefinitionRepository->getClassDefinitions();

        foreach ($classDefinitions as $class) {
            if (!isset($mapping[$class->getId()])) {
                continue;
            }
            $classMapping = $mapping[$class->getId()];
            $compactLayouts[] = $this->hydrateCompactLayout($class);

            foreach ($classMapping as $layout) {
                $compactLayouts[] = $this->hydrateCompactLayout($class, $layout);
            }

        }

        return $compactLayouts;
    }

    public function getTextLayoutPreview(TextLayoutPreviewParameters $parameters): string
    {
        $object = $this->getPreviewObject($parameters);
        $textLayout = new Text();
        $textLayout->setName('textLayoutPreview' . $parameters->getClassName());
        $textLayout = $this->setPreviewRendering($textLayout, $parameters);
        if ($parameters->getHtml() !== null) {
            $textLayout->setHtml($parameters->getHtml());
        }

        try {
            return $textLayout->enrichLayoutDefinition($object, ['data' => $parameters->getRenderingData()])->getHtml();

        } catch (Exception $e) {
            throw new EnvironmentException($e->getMessage());
        }
    }

    private function hydrateCompactLayout(
        ClassDefinition $classDefinition,
        ?CustomLayout $layout = null
    ): LayoutCompact {
        $compactLayout = $this->compactLayoutHydrator->hydrate($classDefinition, $layout);
        $this->eventDispatcher->dispatch(
            new CompactLayoutCollectionEvent($compactLayout),
            CompactLayoutCollectionEvent::EVENT_NAME
        );

        return $compactLayout;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    private function getPreviewObject(TextLayoutPreviewParameters $parameters): ?Concrete
    {
        if ($parameters->getPath() !== '' && $parameters->getPath() !== null) {
            $object = $this->dataObjectService->getDataObjectElementByPath(
                $this->securityService->getCurrentUser(),
                $parameters->getPath()
            );

            if (!$object instanceof Concrete) {
                return null;
            }

            return $object;
        }

        $this->classDefinitionRepository->getClassDefinition($parameters->getClassName());
        $className = '\\Pimcore\\Model\\DataObject\\' . $parameters->getClassName();

        return new $className();
    }

    private function setPreviewRendering(Text $textLayout, TextLayoutPreviewParameters $parameters): Text
    {
        $renderingClass = $parameters->getRenderingClass();
        $renderingData = $parameters->getRenderingData() ?? '';
        if ($renderingClass === null || $renderingClass === '') {
            return $textLayout;
        }

        $textLayout->setRenderingClass($renderingClass);
        $textLayout->setRenderingData($renderingData);

        return $textLayout;
    }
}
