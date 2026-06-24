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
use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\CoreElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ExportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\Resolver\ResolverTypeGuesserInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\TransformerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\StaticTextConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\Transformer;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\TransformerLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\AdvancedValue;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\FieldDefinitionTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Trait\LocalizedValueTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_filter;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_object;
use function is_scalar;
use function sprintf;

/**
 * @internal
 */
final class AdvancedColumnResolver implements
    ColumnResolverInterface,
    CoreElementColumnResolverInterface,
    ExportResolverInterface
{
    use FieldDefinitionTrait;
    use LocalizedValueTrait;

    /**
     * @var AdvancedValue[]
     */
    private array $values = [];

    /**
     * @var array <string, TransformerInterface>
     */
    private array $transformers = [];

    private ?UserInterface $user = null;

    public function __construct(
        private readonly TransformerLoaderInterface $transformerLoader,
        private readonly GridServiceInterface $gridService,
        private readonly ResolverTypeGuesserInterface $resolverTypeGuesser,
        private readonly ToolResolverInterface $toolResolver,
        private readonly LocalizedFieldResolverInterface $localizedFieldResolver,
    ) {
    }

    /** @see LocalizedValueTrait::doGetFallbackValues() */
    protected function doGetFallbackValues(): bool
    {
        return $this->localizedFieldResolver->doGetFallbackValues();
    }

    /** @see LocalizedValueTrait::getDefaultLanguage() */
    protected function getDefaultLanguage(): ?string
    {
        return $this->toolResolver->getDefaultLanguage();
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
        $this->doResolve($column, $element);

        $this->doApplyTransformers($column);

        return new ColumnData(
            key: $column->getKey(),
            locale: $column->getLocale(),
            value: $this->values,
            fieldType: 'advanced'
        );
    }

    /**
     * @throws Exception
     */
    public function resolveForExport(Column $column, ElementInterface $element, UserInterface $user): ColumnData
    {
        $this->user = $user;

        /*
         * If no transformers are configured, call export resolver directly
         * Otherwise, call core resolver and apply transformers afterwards
         */
        $this->doResolve($column, $element, empty($column->getAdvancedColumnConfig()->getTransformers()));

        $this->doApplyTransformers($column);

        $returnValue = [];
        foreach ($this->values as $value) {
            if ($this->isStringConvertible($value->getValue())) {
                $returnValue[] = $value->getValue();

                continue;
            }

            try {
                $returnValue[] = json_encode($value->getValue(), JSON_THROW_ON_ERROR);
            } catch (JsonException) {
                $returnValue[] = 'Unable to export value';
            }
        }

        return new ColumnData(
            key: $column->getKey(),
            locale: $column->getLocale(),
            value: implode('|', $returnValue),
            fieldType: 'advanced'
        );

    }

    private function isStringConvertible(mixed $value): bool
    {
        if (is_scalar($value)) {
            return true;
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return true;
        }

        return false;
    }

    /**
     * @throws Exception
     */
    private function doResolve(Column $column, ElementInterface $element, bool $export = false): void
    {
        $this->values = [];
        if (!$element instanceof Concrete) {
            throw new InvalidArgumentException('Element must be a concrete object');
        }

        foreach ($column->getAdvancedColumnConfig()->getColumns() as $advancedColumn) {
            if ($advancedColumn instanceof SimpleFieldConfig) {
                $this->resolveField($advancedColumn, $column, $element, $export);
            }

            if ($advancedColumn instanceof RelationFieldConfig) {
                $this->resolveRelationField($advancedColumn, $column, $element, $export);
            }

            if ($advancedColumn instanceof StaticTextConfig) {
                $this->values[] = new AdvancedValue(
                    type: 'string',
                    value: $advancedColumn->getText(),
                    fieldName: $column->getKey()
                );
            }
        }
    }

    /**
     * @throws Exception
     */
    private function resolveField(
        SimpleFieldConfig|RelationFieldConfig $fieldConfig,
        Column $column,
        Concrete $element,
        bool $export = false
    ): void {
        $resolverType = $this->resolverTypeGuesser->guessType(
            $fieldConfig->getField(),
            $element->getClassId(),
            $this->user
        );

        $isLocalizable = $this->resolverTypeGuesser->isLocalizable(
            $fieldConfig->getField(),
            $element->getClassId(),
            $this->user
        );

        $config = $column->getConfig();
        if ($resolverType === 'dataobject.classificationstore') {
            $config = [
                'groupId' => $fieldConfig->getGroupId(),
                'keyId' => $fieldConfig->getKeyId(),
            ];
        }

        $resolver = $this->gridService->getColumnResolvers()[$resolverType];

        $subColumn = new Column(
            key: $fieldConfig->getField(),
            locale: $isLocalizable ? $column->getLocale() : null,
            type: $resolverType,
            group: $column->getGroup(),
            config: $config,
        );

        $data = null;
        if ($resolver instanceof CoreElementColumnResolverInterface && !$export) {
            $data = $resolver->resolveForCoreElement($subColumn, $element);
        }

        if ($resolver instanceof ExportResolverInterface && $export) {
            $data = $resolver->resolveForExport($subColumn, $element, $this->user);
        }

        $relationName = null;
        if ($fieldConfig instanceof RelationFieldConfig) {
            $relationName = $fieldConfig->getRelation();
        }

        if (!$data) {
            return;
        }

        $this->values[] = new AdvancedValue(
            type: $data->getFieldType(),
            value: $data->getValue(),
            fieldName: $fieldConfig->getField(),
            relation: $relationName
        );
    }

    /**
     * @throws Exception
     */
    private function resolveRelationField(
        RelationFieldConfig $relationFieldConfig,
        Column $column,
        Concrete $element,
        bool $export = false
    ): void {
        $relatedElements = $this->getRelatedElements($relationFieldConfig->getRelation(), $column, $element);
        foreach ($relatedElements as $relationElement) {
            $this->resolveField($relationFieldConfig, $column, $relationElement, $export);
        }
    }

    /**
     * @return Concrete[]
     *
     * @throws Exception
     */
    private function getRelatedElements(string $relationKey, Column $column, Concrete $element): array
    {
        $isRelationLocalizable = $this->resolverTypeGuesser->isLocalizable(
            $relationKey,
            $element->getClassId(),
            $this->user
        );

        $relation = $this->getLocalizedValueFromKey(
            $relationKey,
            $isRelationLocalizable ? $column->getLocale() : null,
            $element
        );

        if (is_array($relation)) {
            return array_values(
                array_filter($relation, static fn ($relationElement) => $relationElement instanceof Concrete)
            );
        }

        if (!$relation instanceof Concrete) {
            return [];
        }

        return [$relation];
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

    private function doApplyTransformers(Column $column): void
    {
        try {
            $this->applyTransformers($column->getAdvancedColumnConfig()->getTransformers());
        } catch (TransformerException $exception) {
            $this->values = [
                new AdvancedValue(
                    type: 'error',
                    value: sprintf(
                        'Error applying transformer: %s',
                        $exception->getMessage(),
                    ),
                    fieldName: $column->getKey()
                ),
            ];
        }
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

            $this->values = $this->getTransformers()[$transformer->getKey()]->transform(
                $this->values,
                $transformer->getConfig()
            );
        }
    }
}
