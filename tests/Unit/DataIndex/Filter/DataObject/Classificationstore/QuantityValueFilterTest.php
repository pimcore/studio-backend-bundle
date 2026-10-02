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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\DataObject\Classificationstore;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\NumberFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\QuantityValueFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final class QuantityValueFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    private const KEY_NAME = 'weight';

    private const GROUP_NAME = 'dimensions';

    public function testIsSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyFilter(['setting' => 'is', 'is' => 5, 'unitId' => 'kg']);

        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(NumberFilter::class, $filter);
        $this->assertSame('weight.value', $filter->getFieldName());
        $this->assertSame(5, $filter->getSearchTerm());
        $this->assertUnitFilter($calls[1]['filter']);
    }

    public function testLessSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyFilter(['setting' => 'less', 'to' => 10, 'unitId' => 'kg']);

        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertNull($filter->getMin());
        $this->assertSame(10, $filter->getMax());
        $this->assertUnitFilter($calls[1]['filter']);
    }

    public function testMoreSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyFilter(['setting' => 'more', 'from' => 3, 'unitId' => 'kg']);

        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertSame(3, $filter->getMin());
        $this->assertNull($filter->getMax());
        $this->assertUnitFilter($calls[1]['filter']);
    }

    public function testBetweenSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyFilter(['setting' => 'between', 'from' => 3, 'to' => 10, 'unitId' => 'kg']);

        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertSame(3, $filter->getMin());
        $this->assertSame(10, $filter->getMax());
        $this->assertUnitFilter($calls[1]['filter']);
    }

    private function assertRangeFilter(mixed $filter): NumberRangeFilter
    {
        $this->assertInstanceOf(NumberRangeFilter::class, $filter);
        $this->assertSame('weight.value', $filter->getField());

        return $filter;
    }

    private function assertUnitFilter(mixed $filter): void
    {
        $this->assertInstanceOf(WildcardSearch::class, $filter);
        $this->assertSame('weight.unitId', $filter->getFieldName());
        $this->assertSame('kg', $filter->getSearchTerm());
    }

    /**
     * @return array<int, array{fieldName: string, group: string, filter: mixed}>
     */
    private function applyFilter(array $value): array
    {
        $calls = [];
        $query = null;
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'classificationStoreFilter' => function (
                string $fieldName,
                string $group,
                mixed $filter
            ) use (&$calls, &$query): mixed {
                $calls[] = ['fieldName' => $fieldName, 'group' => $group, 'filter' => $filter];

                return $query;
            },
        ]);

        $key = new KeyGroupRelation();
        $key->setName(self::KEY_NAME);
        $group = new GroupConfig();
        $group->setName(self::GROUP_NAME);

        $filter = new QuantityValueFilter(
            $this->makeEmpty(GroupConfigRepositoryInterface::class, ['getById' => $group]),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, ['getByKeyGroupId' => $key])
        );

        $parameter = $this->getColumnFilterMock(
            'classificationStoreField',
            ColumnType::CLASSIFICATION_STORE_QUANTITY_VALUE->value,
            ['groupId' => 1, 'keyId' => 2, 'value' => $value]
        );

        $filter->apply($parameter, $query);

        return $calls;
    }
}
