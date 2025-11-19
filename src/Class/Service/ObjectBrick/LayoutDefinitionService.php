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
use Pimcore\Bundle\StudioBackendBundle\Class\Event\ObjectBrick\LayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\ObjectBrick\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\ObjectBrick\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinitionInterface;
use Pimcore\Model\DataObject\Concrete;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function get_class;
use function sprintf;

/**
 * @internal
 */
final class LayoutDefinitionService implements LayoutDefinitionServiceInterface
{
    public function __construct(
        private readonly DataObjectResolverInterface $dataObjectResolver,
        private readonly DataObjectServiceResolverInterface $dataObjectServiceResolver,
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DefinitionResolverInterface $definitionResolver,
        private readonly LayoutDefinitionHydratorInterface $layoutDefinitionHydrator,
        private readonly EventDispatcherInterface $eventDispatcher,
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
                sprintf('DataObject class (%s) is not a concrete object', get_class($dataObject))
            );
        }

        $classDef = $this->classDefinitionResolver->getById($dataObject->getClassId());
        if (!$classDef instanceof ClassDefinitionInterface) {
            throw new NotFoundException(type: 'class for data object', id: $dataObjectId);
        }

        $this->collectFieldCollectionTypes($classDef);

        $layoutDefinitions = [];
        foreach ($this->collectFieldCollectionTypes($classDef) as $field => $types) {
            foreach ($types as $type) {
                $layoutDefinitions[] = $this->getLayoutDefinitionByType($type, $field, $dataObject);
            }
        }

        return $layoutDefinitions;

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

    private function collectFieldCollectionTypes(ClassDefinitionInterface $classDefinition): array
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
