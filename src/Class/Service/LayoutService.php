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


use Pimcore\Bundle\StudioBackendBundle\Class\Event\CompactLayoutCollectionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\CompactLayoutHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\CustomLayoutRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\LayoutCompact;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\CustomLayout;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class LayoutService implements LayoutServiceInterface
{
    public function __construct(
        private ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private CompactLayoutHydratorInterface $compactLayoutHydrator,
        private CustomLayoutRepositoryInterface $customLayoutRepository,
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

    private function hydrateCompactLayout(
        ClassDefinition $classDefinition,
        ?CustomLayout $layout = null
    ): LayoutCompact
    {
        $compactLayout = $this->compactLayoutHydrator->hydrate($classDefinition, $layout);
        $this->eventDispatcher->dispatch(
            new CompactLayoutCollectionEvent($compactLayout),
            CompactLayoutCollectionEvent::EVENT_NAME
        );

        return $compactLayout;
    }
}
