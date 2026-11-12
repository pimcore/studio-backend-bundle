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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\FilterUnavailableReason;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\InputQuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\MultiValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\QuantityValue;
use Pimcore\Model\DataObject\ClassDefinition\Data\RgbaColor;
use Pimcore\Model\DataObject\Classificationstore\KeyFilter;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Throwable;
use function array_merge;
use function in_array;
use function json_decode;

/**
 * @internal
 */
final readonly class KeyFilterResolver implements KeyFilterResolverInterface
{
    public function __construct(
        private ServiceResolverInterface $serviceResolver,
        private SearchIndexFlagResolverInterface $searchIndexFlagResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterTypes(Data $definition): array
    {
        // a multiValue key is filtered as the type it wraps, which declares the filter
        if ($definition instanceof MultiValue) {
            $delegate = $definition->getDelegate();

            return $delegate instanceof Data ? $this->getFilterTypes($delegate) : [];
        }

        // these two declare the numeric filter and have a filter of their own, which carries the unit
        if ($definition instanceof QuantityValue) {
            return [ColumnType::CLASSIFICATION_STORE_QUANTITY_VALUE->value];
        }

        if ($definition instanceof InputQuantityValue) {
            return [ColumnType::CLASSIFICATION_STORE_INPUT_QUANTITY_VALUE->value];
        }

        // declares nothing in core, and has had a filter here since before the declaration existed
        if ($definition instanceof RgbaColor) {
            return [ColumnType::CLASSIFICATION_STORE_RGBA->value];
        }

        return $this->getTypesOfDeclaration($definition->getClassificationStoreKeyFilter());
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportedFilterType(string $filterType): bool
    {
        $types = [
            ColumnType::CLASSIFICATION_STORE_QUANTITY_VALUE->value,
            ColumnType::CLASSIFICATION_STORE_INPUT_QUANTITY_VALUE->value,
            ColumnType::CLASSIFICATION_STORE_RGBA->value,
        ];
        foreach (KeyFilter::cases() as $declaration) {
            $types = array_merge($types, $this->getTypesOfDeclaration($declaration));
        }

        return in_array($filterType, $types, true);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterUnavailableReason(KeyGroupRelation $relation): ?FilterUnavailableReason
    {
        if (!$this->searchIndexFlagResolver->isIndexed($relation->getDefinition())) {
            return FilterUnavailableReason::INDEX_EXCLUDED;
        }

        $definition = $this->rebuildDefinition($relation);
        if ($definition === null) {
            return FilterUnavailableReason::RESOLUTION_FAILED;
        }

        return $this->getFilterTypes($definition) !== [] ? null : FilterUnavailableReason::TYPE_UNSUPPORTED;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterTypesOf(KeyGroupRelation $relation): array
    {
        $definition = $this->rebuildDefinition($relation);

        return $definition !== null ? $this->getFilterTypes($definition) : [];
    }

    /**
     * The definition of the key's own blob, or null where it cannot be rebuilt: a blob that is not
     * readable, or a type core has no data class for.
     */
    private function rebuildDefinition(KeyGroupRelation $relation): ?Data
    {
        try {
            return $this->serviceResolver->getFieldDefinitionFromJson(
                json_decode($relation->getDefinition(), true) ?? [],
                $relation->getType()
            );
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return string[]
     */
    private function getTypesOfDeclaration(KeyFilter $declaration): array
    {
        return match ($declaration) {
            KeyFilter::Term => [
                ColumnType::CLASSIFICATION_STORE_SELECT->value,
                ColumnType::CLASSIFICATION_STORE_TIME->value,
            ],
            KeyFilter::FullText => [ColumnType::CLASSIFICATION_STORE_STRING->value],
            KeyFilter::Numeric => [ColumnType::CLASSIFICATION_STORE_NUMBER->value],
            KeyFilter::Date => [
                ColumnType::CLASSIFICATION_STORE_DATE->value,
                ColumnType::CLASSIFICATION_STORE_DATETIME->value,
            ],
            KeyFilter::Boolean => [ColumnType::CLASSIFICATION_STORE_BOOLEAN->value],
            KeyFilter::None => [],
        };
    }
}
