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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\FieldCollection\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\ConfigLayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Event\FieldCollection\LayoutDefinitionEvent;
use Pimcore\Bundle\StudioBackendBundle\Class\Hydrator\FieldCollection\LayoutDefinitionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\FieldCollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\LayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\ClassDefinition\Data\Fieldcollections;
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
        private readonly FieldCollectionRepositoryInterface $fieldCollectionRepository,
        private readonly LayoutDefinitionHydratorInterface $layoutDefinitionHydrator,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    private array $fieldCollectionTypes = [];

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

        $this->collectFieldCollectionTypes(
            $this->classDefinitionResolver->getById($dataObject->getClassId())
        );

        $layoutDefinitions = [];
        foreach ($this->fieldCollectionTypes as $fieldCollectionType) {
            $layoutDefinitions[] = $this->getLayoutDefinitionByType($fieldCollectionType, $dataObject);
        }

        return $layoutDefinitions;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayoutDefinitionByKey(string $key): ConfigLayoutDefinition
    {
        $definition = $this->fieldCollectionRepository->getFieldCollectionByKey($key);
        $layout = $definition->getLayoutDefinitions();

        if ($layout === null) {
            throw new EnvironmentException(
                sprintf('Layout definition for field collection "%s" is not available', $key)
            );
        }

        $configLayoutDefinition = $this->layoutDefinitionHydrator->hydrateConfigLayoutDefinition($layout);

        $this->eventDispatcher->dispatch(
            new ConfigLayoutDefinitionEvent($configLayoutDefinition),
            ConfigLayoutDefinitionEvent::EVENT_NAME
        );

        return $configLayoutDefinition;
    }

    /**
     * @throws Exception
     */
    private function getLayoutDefinitionByType(string $name, Concrete $dataObject): LayoutDefinition
    {
        $definition = $this->definitionResolver->getByKey($name);

        if (!$definition) {
            throw new NotFoundException('Field Collection Definition', $name);
        }
        $layoutDefinitions = $definition->getLayoutDefinitions();
        $this->dataObjectServiceResolver->enrichLayoutDefinition($layoutDefinitions, $dataObject);
        $layoutDefinition = $this->layoutDefinitionHydrator->hydrate($definition);

        $this->eventDispatcher->dispatch(
            new LayoutDefinitionEvent($layoutDefinition),
            LayoutDefinitionEvent::EVENT_NAME
        );

        return $layoutDefinition;
    }

    private function collectFieldCollectionTypes(ClassDefinitionInterface $classDefinition): void
    {
        foreach ($classDefinition->getFieldDefinitions() as $fieldDefinition) {
            if ($fieldDefinition instanceof Fieldcollections) {
                $this->fieldCollectionTypes = [...$this->fieldCollectionTypes, ...$fieldDefinition->getAllowedTypes()];
            }
        }
    }
}
