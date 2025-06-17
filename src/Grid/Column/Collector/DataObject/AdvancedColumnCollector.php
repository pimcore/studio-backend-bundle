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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
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
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\RelationField;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\SimpleField;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use function array_key_exists;

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
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly TransformerLoaderInterface $transformerLoader,
    ) {
    }

    public function getCollectorName(): string
    {
        return 'data-object-advanced-column';
    }

    public function getColumnConfigurations(array $availableColumnDefinitions): array
    {
        $test = $this->classDefinitionService->getFilteredLayoutDefinitions(
            $this->getClassId(),
            $this->getFolderId(),
            $this->getUser()
        );

        $children = $test->getChildren();

        $collectedDefinitions = $this->collectSupportedDefinitions($children);

        return [$this->buildColumnConfigurations(
            $this->getSimpleFields($collectedDefinitions),
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
            group: 'advanced',
            sortable: false,
            editable: false,
            exportable: true,
            filterable: false,
            localizable: true,
            locale: null,
            type: 'dataobject.advanced',
            frontendType: FrontendType::INPUT->value,
            config: [
                [
                    'simpleField' => $simpleFields,
                    'relationField' => $relationFields,
                    'transformers' => $transformers,
                ],
            ],
        );
    }

    /**
     * @return SimpleField[]
     */
    private function getSimpleFields(array $groupedDefinitions): array
    {
        $simpleFields = [];
        foreach ($groupedDefinitions as $definition) {
            if (!$definition instanceof AbstractRelations) {
                $simpleFields[] = new SimpleField(
                    name: $definition->getTitle(),
                    key: $definition->getName(),
                );
            }
        }

        return $simpleFields;
    }

    /**
     * @return TransformerInterface[]
     */
    public function getTransformers(): array
    {
        return $this->transformerLoader->loadTransformers();
    }

    /**
     * @return RelationField[]
     */
    private function getRelationFields(
        array $groupedDefinitions,
    ): array {
        $relations = [];
        foreach ($groupedDefinitions as $definition) {
            if ($definition instanceof AdvancedManyToManyObjectRelation) {
                $relations[] = $this->buildAdvancedManyToManyObjectRelationFields($definition);

                continue;
            }

            if ($definition instanceof AbstractRelations) {
                $relations[] = $this->buildAbstractRelationsFields($definition);
            }
        }

        return $relations;

    }

    private function buildAbstractRelationsFields(
        AbstractRelations $definition,
    ): RelationField {
        $classes  = $definition->getClasses();
        $fields = [];
        foreach ($classes as $class) {
            if (!array_key_exists('classes', $class)) {
                continue;
            }

            $fields = [
                ...$this->buildFieldForClassName($class['classes']),
                ...$fields,
            ];
        }

        return new RelationField(
            name: $definition->getTitle(),
            key: $definition->getName(),
            fields: $fields
        );
    }

    private function buildAdvancedManyToManyObjectRelationFields(
        AdvancedManyToManyObjectRelation $definition,
    ): RelationField {
        $className = $definition->getAllowedClassId();
        if ($className === null) {
            throw new InvalidArgumentException('Class name is required');
        }

        return new RelationField(
            name: $definition->getTitle(),
            key: $definition->getName(),
            fields: $this->buildFieldForClassName($className)
        );
    }

    /**
     * @return SimpleField[]
     */
    private function buildFieldForClassName(string $className): array
    {
        try {
            $definitionOfTheRelation = $this->classDefinitionResolver->getByName($className);
        } catch (Exception $e) {
            throw new NotFoundException('Class definition', $className);
        }

        if ($definitionOfTheRelation === null) {
            throw new NotFoundException('Class definition', $className);
        }

        $test = $this->classDefinitionService->getFilteredLayoutDefinitions(
            $definitionOfTheRelation->getId(),
            $this->getFolderId(),
            $this->getUser()
        );

        return $this->getSimpleFields(
            $this->collectSupportedDefinitions($test->getChildren())
        );
    }
}
