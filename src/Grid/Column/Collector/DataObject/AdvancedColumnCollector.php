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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Collector\DataObject;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\Objectbrick\DefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ClassIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FolderIdInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\FrontendType;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseClassIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseFolderIdTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseUserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\UseUserTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ClassDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SystemColumnServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\RelationField;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\SimpleField;
use Pimcore\Bundle\StudioBackendBundle\ObjectBrick\Service\ObjectBrickServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Objectbricks;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use function count;
use function in_array;

/**
 * @internal
 */
final class AdvancedColumnCollector implements
    ColumnCollectorInterface,
    ClassIdInterface,
    FolderIdInterface,
    UseUserInterface
{
    use UseClassIdTrait;
    use UseFolderIdTrait;
    use UseUserTrait;

    public function __construct(
        private readonly ClassDefinitionServiceInterface $classDefinitionService,
        private readonly ClassDefinitionRepositoryInterface $classRepository,
        private readonly TransformerLoaderInterface $transformerLoader,
        private readonly DefinitionResolverInterface $objectBrickdefinitionResolver,
        private readonly ObjectBrickServiceInterface $objectBrickService,
        private readonly SystemColumnServiceInterface $systemColumnService,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'data-object-advanced-column';
    }

    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $layoutDefinitions = $this->classDefinitionService->getFilteredLayoutDefinitions(
            $this->getClassId(),
            $this->getFolderId(),
            $this->getUser()
        );

        if ($layoutDefinitions === null) {
            return [];
        }

        $children = $layoutDefinitions->getChildren();

        $collectedDefinitions = $this->collectSupportedDefinitions($children);

        return [$this->buildColumnConfigurations(
            $this->getDefaultFields($collectedDefinitions),
            $this->getRelationFields($collectedDefinitions),
            $this->getTransformers()
        )];
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_DATA_OBJECT,
        ];
    }

    private function collectSupportedDefinitions(
        array $definitions,
    ): array {
        $groupedDefinitions = [];
        foreach ($definitions as $definition) {
            if ($definition instanceof Layout) {
                $groupedDefinitions = [
                    ...$this->collectSupportedDefinitions($definition->getChildren()),
                    ...$groupedDefinitions,
                ];

                continue;
            }

            if ($definition instanceof Localizedfields) {
                $groupedDefinitions = [
                    ...$this->collectSupportedDefinitions($definition->getChildren()),
                    ...$groupedDefinitions,
                ];

                continue;
            }

            $groupedDefinitions[] = $definition;

        }

        return $groupedDefinitions;
    }

    /**
     * @param SimpleField[] $simpleFields
     * @param RelationField[] $relationFields
     * @param TransformerInterface[]  $transformers
     */
    private function buildColumnConfigurations(
        array $simpleFields,
        array $relationFields,
        array $transformers
    ): ColumnConfiguration {
        return new ColumnConfiguration(
            key: 'advanced',
            group: ['advanced'],
            sortable: false,
            editable: false,
            exportable: true,
            filterable: false,
            localizable: true,
            locale: null,
            type: 'dataobject.advanced',
            frontendType: FrontendType::INPUT->value,
            config:
                [
                    'simpleField' => $simpleFields,
                    'relationField' => $relationFields,
                    'transformers' => $transformers,
                ]
        );
    }

    /**
     * @return SimpleField[]
     */
    private function getDefaultFields(array $groupedDefinitions): array
    {
        $simpleFields = $this->getSystemFields();
        foreach ($groupedDefinitions as $definition) {
            if ($definition instanceof Classificationstore) {
                $simpleFields[] = $this->buildClassificationStoreField($definition);

                continue;
            }

            if ($definition instanceof Objectbricks) {
                $simpleFields = [
                    ...$this->buildObjectBricksFields($definition),
                    ...$simpleFields,
                ];

                continue;
            }

            if (!$definition instanceof AbstractRelations) {
                if ($definition->getInvisible()) {
                    continue;
                }

                $simpleFields[] = new SimpleField(
                    name: $definition->getTitle(),
                    key: $definition->getName(),
                );
            }
        }

        return $simpleFields;
    }

    private function buildClassificationStoreField(Classificationstore $definition): SimpleField
    {
        return new SimpleField(
            name: $definition->getTitle(),
            key: $definition->getName(),
            config: [
                'classificationStore' => true,
                'storeId' => $definition->getStoreId(),
            ],
        );
    }

    private function getSystemFields(): array
    {
        $systemColumns = $this->systemColumnService->getSystemColumnsForDataObjects();

        foreach ($systemColumns as $key => $systemField) {
            $systemFields[] = new SimpleField(
                name: $key,
                key: $key,
            );
        }

        return $systemFields ?? [];
    }

    /**
     * @return TransformerInterface[]
     */
    public function getTransformers(): array
    {
        return $this->transformerLoader->loadTransformers();
    }

    /**
     * @return SimpleField[]
     */
    private function buildObjectBricksFields(Objectbricks $brick): array
    {
        $allowedBricks = $brick->getAllowedTypes();

        $fields = [];
        foreach ($allowedBricks as $brickType) {
            $objectBrickDefinition = $this->objectBrickdefinitionResolver->getByKey($brickType);
            if ($objectBrickDefinition === null) {
                continue;
            }

            $objectBrickItems = $this->objectBrickService->getDataFields(
                $objectBrickDefinition->getLayoutDefinitions()
            );

            foreach ($objectBrickItems as $objectBrickItem) {
                $fieldDefinition = $objectBrickItem->getFieldDefinition();
                if ($fieldDefinition->getInvisible()) {
                    continue;
                }

                $fields[] = new SimpleField(
                    name: $fieldDefinition->getTitle(),
                    key: $brick->getName() . '.' . $brickType . '.' . $fieldDefinition->getName()
                );
            }
        }

        return $fields;
    }

    /**
     * @return RelationField[]
     */
    private function getRelationFields(
        array $groupedDefinitions,
    ): array {
        $relations = [];
        foreach ($groupedDefinitions as $definition) {

            if ($definition instanceof AbstractRelations) {
                if ($definition->getInvisible()) {
                    continue;
                }

                $relations[] = $this->buildRelationFields($definition);
            }
        }

        return $relations;

    }

    private function buildRelationFields(
        AbstractRelations $definition,
    ): RelationField {
        $classes = $definition->getClasses();
        $classIds = [];
        $fieldsByClass = [];

        foreach ($classes as $class) {
            if ($this->isFolderPseudoClass($class['classes'])) {
                continue;
            }

            try {
                $classDefinition = $this->classRepository->getClassDefinition($class['classes']);
            } catch (NotFoundException) {
                // The class configured on this relation no longer exists (e.g. it was renamed
                // without updating other classes' relation field configuration) - skip it.
                continue;
            }

            $classIds[] = $classDefinition->getId();
            $fieldsByClass[] = $this->buildFieldForClassName($class['classes']);
        }

        $fields = $this->intersectFieldsByKey($fieldsByClass);

        return new RelationField(
            name: $definition->getTitle(),
            key: $definition->getName(),
            classIds: $classIds,
            fields: $fields
        );
    }

    private function isFolderPseudoClass(string $className): bool
    {
        return $className === ElementTypes::TYPE_FOLDER;
    }

    /**
     * @param SimpleField[][] $fieldsByClass
     *
     * @return SimpleField[]
     *
     * Only fields with the same key are returned.
     */
    private function intersectFieldsByKey(array $fieldsByClass): array
    {
        if (empty($fieldsByClass)) {
            return [];
        }

        if (count($fieldsByClass) === 1) {
            return $fieldsByClass[0];
        }

        $keyGroups = array_map(
            static fn (array $fields) => array_map(static fn (SimpleField $f) => $f->getKey(), $fields),
            $fieldsByClass
        );

        $commonKeys = array_intersect(...$keyGroups);

        return array_values(array_filter(
            $fieldsByClass[0],
            static fn (SimpleField $f) => in_array($f->getKey(), $commonKeys, true)
        ));
    }

    /**
     * @return SimpleField[]
     */
    private function buildFieldForClassName(string $className): array
    {
        $definitionOfTheRelation = $this->classRepository->getClassDefinition($className);

        $filteredLayoutDefinitions = $this->classDefinitionService->getFilteredLayoutDefinitions(
            $definitionOfTheRelation->getId(),
            $this->getFolderId(),
            $this->getUser()
        );

        if ($filteredLayoutDefinitions === null) {
            return [];
        }

        return $this->getDefaultFields(
            $this->collectSupportedDefinitions($filteredLayoutDefinitions->getChildren())
        );
    }
}
