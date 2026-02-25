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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\ObjectBrick;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\ConfigLayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\LayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ConfigLayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ObjectBrickRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class LayoutDefinitionService implements LayoutDefinitionServiceInterface
{
    public function __construct(
        private DataObjectResolverInterface $dataObjectResolver,
        private DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private ClassDefinitionResolverInterface $classDefinitionResolver,
        private DefinitionResolverInterface $definitionResolver,
        private LayoutDefinitionHydratorInterface $layoutDefinitionHydrator,
        private ObjectBrickRepositoryInterface $objectBrickRepository,
        private ConfigLayoutDefinitionHydratorInterface $configLayoutDefinitionHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinitionsForObject(int $dataObjectId): array
    {
        $dataObject = $this->dataObjectResolver->getById($dataObjectId);
        if (!$dataObject instanceof Concrete) {
            throw new InvalidElementTypeException(
                sprintf('DataObject id (%s) is not a concrete object', $dataObjectId)
            );
        }

        $classDef = $this->classDefinitionResolver->getById($dataObject->getClassId());
        if (!$classDef instanceof ClassDefinitionInterface) {
            throw new NotFoundException(type: 'class for data object', id: $dataObjectId);
        }

        $objectBrickTypes = $this->collectObjectBrickTypes($classDef);

        $layoutDefinitions = [];
        foreach ($objectBrickTypes as $field => $types) {
            foreach ($types as $type) {
                $layoutDefinitions[] = $this->getLayoutDefinitionByType($type, $field, $dataObject);
            }
        }

        return $layoutDefinitions;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinitionByKey(string $key): ConfigLayoutDefinition
    {
        $definition = $this->objectBrickRepository->getObjectBrickByKey($key);
        $layout = $definition->getLayoutDefinitions();

        if (!$layout instanceof Panel) {
            throw new NotFoundException('layout for objectbrick', $key);
        }

        $configLayoutDefinition = $this->configLayoutDefinitionHydrator->hydrate($layout);

        $this->eventDispatcher->dispatch(
            new ConfigLayoutDefinitionEvent($configLayoutDefinition),
            ConfigLayoutDefinitionEvent::EVENT_NAME
        );

        return $configLayoutDefinition;
    }

    /**
     * @throws Exception
     */
    private function getLayoutDefinitionByType(string $name, string $field, Concrete $dataObject): LayoutDefinition
    {
        $definition = $this->definitionResolver->getByKey($name);

        if (!$definition) {
            throw new NotFoundException('Object Brick Definition', $name);
        }

        $layoutDefinitions = $definition->getLayoutDefinitions();
        $this->dataObjectServiceResolver->enrichLayoutDefinition(
            $layoutDefinitions,
            $dataObject,
            [
                'containerType' => 'objectbrick',
                'containerKey' => $name,
                'outerFieldname' => $field,
            ]
        );
        $layoutDefinition = $this->layoutDefinitionHydrator->hydrate($definition);

        $this->eventDispatcher->dispatch(
            new LayoutDefinitionEvent($layoutDefinition),
            LayoutDefinitionEvent::EVENT_NAME
        );

        return $layoutDefinition;
    }

    private function collectObjectBrickTypes(ClassDefinitionInterface $classDefinition): array
    {
        $objectBrickTypes = [];
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Objectbricks) {
                $objectBrickTypes[$fieldDefinition->getName()] = $fieldDefinition->getAllowedTypes();
            }
        }

        return $objectBrickTypes;
    }
}
