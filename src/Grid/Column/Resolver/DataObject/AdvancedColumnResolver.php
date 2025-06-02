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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\DataObject;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\ExistingColumnConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\StaticTextConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\Transformer;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\FieldDefinitionTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use function array_key_exists;
use function is_array;
use function sprintf;
use function strval;

/**
 * @internal
 */
final class AdvancedColumnResolver implements ColumnResolverInterface, CoreElementColumnResolverInterface
{
    use FieldDefinitionTrait;
    use LocalizedValueTrait;

    /**
     * @var string[]
     */
    private array $values = [];

    /**
     * @var string[]
     */
    private array $cache = [];

    /**
     * @var array <string, TransformerInterface>
     */
    private array $transformers = [];

    public function __construct(
        private readonly ClassDefinitionResolverInterface $classDefinitionResolver,
        private readonly DataServiceInterface $dataService,
        private readonly TransformerLoaderInterface $transformerLoader,
    ) {
    }

    public function getType(): string
    {
        return 'dataobject.advanced';
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_OBJECT,
        ];
    }

    /**
     * @throws Exception
     */
    public function resolveForCoreElement(Column $column, ElementInterface $element): ColumnData
    {
        $this->cache[] = $column->getKey();
        $this->values = [];
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        foreach ($column->getAdvancedColumnConfig()->getColumns() as $advancedColumn) {
            if ($advancedColumn instanceof SimpleFieldConfig) {
                $this->resolveField($advancedColumn, $column, $element);
            }

            if ($advancedColumn instanceof RelationFieldConfig) {
                $this->resolveRelationField($advancedColumn, $column, $element);
            }

            if ($advancedColumn instanceof StaticTextConfig) {
                $this->values[] = $advancedColumn->getText();
            }

            if ($advancedColumn instanceof ExistingColumnConfig) {
                $this->resolveExistingColumnConfig($advancedColumn);
            }

        }

        $this->applyTransformers($column->getAdvancedColumnConfig()->getTransformers());


        $this->cache[$column->getKey()] = implode(
            $column->getAdvancedColumnConfig()->getConcatenationSymbol(),
            $this->values
        );

        return new ColumnData(
            key: $column->getKey(),
            locale: $column->getLocale(),
            value: $this->cache[$column->getKey()]
        );
    }

    private function resolveExistingColumnConfig(ExistingColumnConfig $columnConfig): void
    {
        if (!array_key_exists($columnConfig->getExistingColumnName(), $this->cache)) {
            throw new InvalidArgumentException(sprintf(
                'Advanced Column %s is not resolved yet. Please resolve it before using it as existing column config.',
                $columnConfig->getExistingColumnName()
            ));
        }

        $this->values[] = $this->cache[$columnConfig->getExistingColumnName()];
    }

    /**
     * @throws Exception
     */
    private function resolveField(
        SimpleFieldConfig|RelationFieldConfig $fieldConfig,
        Column $column,
        Concrete $element
    ): void {
        $classDefinition = $this->classDefinitionResolver->getById($element->getClassId());
        $fieldDefinition = $this->getFieldDefinition($fieldConfig->getField(), $classDefinition);
        $value = $this->dataService->getNormalizedValue(
            $this->getLocalizedValueFromKey($fieldConfig->getField(), $column->getLocale(), $element),
            $fieldDefinition
        );

        if (!$value) {
            return;
        }

        try {
            $this->values[] = strval($value);
        } catch (Exception $e) {
            throw new InvalidArgumentException(sprintf('Field %s is not a string', $fieldConfig->getField()));
        }
    }

    /**
     * @throws Exception
     */
    private function resolveRelationField(
        RelationFieldConfig $relationFieldConfig,
        Column $column,
        Concrete $element
    ): void {
        $relation = $this->getLocalizedValueFromKey(
            $relationFieldConfig->getRelation(),
            $column->getLocale(),
            $element
        );

        if (is_array($relation)) {
            foreach ($relation as $relationElement) {
                if (!$relationElement instanceof Concrete) {
                    continue;
                }

                $this->resolveField($relationFieldConfig, $column, $relationElement);

            }

            return;
        }

        if (!$relation instanceof Concrete) {
            return;
        }

        $this->resolveField($relationFieldConfig, $column, $relation);
    }

    /**
     * @return array<string, TransformerInterface>
     */
    private function getTransformers(): array
    {
        if (!$this->transformers) {
            $this->transformers = $this->transformerLoader->loadTransformers();
        }

        return $this->transformers;
    }

    /**
     * @param Transformer[] $transformers
     */
    private function applyTransformers(array $transformers): void
    {
        foreach ($transformers as $transformer) {
            if (!array_key_exists($transformer->getKey(), $this->getTransformers())) {
                throw new InvalidArgumentException(sprintf(
                    'Transformer %s is not registered',
                    $transformer->getKey()
                ));
            }

            foreach ($this->values as $index => $value) {
                $this->values[$index] = $this->getTransformers()[$transformer->getKey()]->transform($value);
            }
        }
    }
}
