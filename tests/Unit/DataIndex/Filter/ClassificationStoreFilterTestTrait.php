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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ColumnReferenceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreFilterValueResolver;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ClassificationStoreSegmentResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Utils\ClassificationStoreFilterValue;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;

/**
 * Applies a classification store filter against a recording query. Needs ColumnFilterMockTrait.
 *
 * The segments are what the search index addresses the group and the key by - their identifiers -
 * and the language node is the one the key is written to, which is the column's language only for a
 * key that is localized. The filter takes all three from the resolver rather than from either
 * entity's name or from the column. The resolver is stubbed because what it answers is the search
 * index bundle's rule, asserted there; what these tests assert is that the answer is the one the
 * query is built on.
 *
 * @internal
 */
trait ClassificationStoreFilterTestTrait
{
    private const KEY_SEGMENT = 'weightIdentifier';

    private const GROUP_SEGMENT = 'dimensionsIdentifier';

    private const FIELD_NAME = 'classificationStoreField';

    private const NON_LOCALIZED_NODE = 'default';

    private const COLUMN_LANGUAGE = 'de';

    /**
     * @param class-string<FilterInterface> $filterClass
     *
     * @return array<int, array{fieldName: string, group: string, locale: string|null, filter: mixed}>
     *         the recorded filter calls
     */
    private function applyFilter(
        string $filterClass,
        string $columnType,
        string|array $value,
        ?string $variant = null,
        ?string $columnLocale = null,
        bool $keyLocalized = false
    ): array {
        $calls = [];
        $query = null;
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'classificationStoreFilter' => function (
                string $fieldName,
                string $group,
                mixed $filter,
                ?string $locale = null
            ) use (&$calls, &$query): mixed {
                $calls[] = [
                    'fieldName' => $fieldName,
                    'group' => $group,
                    'locale' => $locale,
                    'filter' => $filter,
                ];

                return $query;
            },
        ]);

        $filter = new $filterClass(
            new ClassificationStoreFilterValueResolver(
                $this->makeEmpty(ColumnReferenceServiceInterface::class)
            ),
            $this->createSegmentResolver($keyLocalized)
        );

        $parameter = $this->getColumnFilterMock(
            self::FIELD_NAME,
            $columnType,
            ['groupId' => 1, 'keyId' => 2, 'value' => $value, ...($variant !== null ? ['variant' => $variant] : [])],
            $columnLocale
        );

        $filter->apply($parameter, $query);

        return $calls;
    }

    private function createSegmentResolver(bool $keyLocalized = false): ClassificationStoreSegmentResolverInterface
    {
        return $this->makeEmpty(ClassificationStoreSegmentResolverInterface::class, [
            'resolveGroupSegment' => self::GROUP_SEGMENT,
            'resolveFieldName' => static function (ClassificationStoreFilterValue $filterValue): string {
                $variant = $filterValue->getVariant();

                return $variant === null ? self::KEY_SEGMENT : self::KEY_SEGMENT . '_' . $variant;
            },
            // the index's own rule: the column's language for a localized key, "default" for any other
            'resolveLocale' => static function (
                ClassificationStoreFilterValue $filterValue,
                ColumnFilter $column,
                mixed $parameters = null
            ) use ($keyLocalized): string {
                return $keyLocalized
                    ? ($column->getLocale() ?? self::NON_LOCALIZED_NODE)
                    : self::NON_LOCALIZED_NODE;
            },
        ]);
    }
}
