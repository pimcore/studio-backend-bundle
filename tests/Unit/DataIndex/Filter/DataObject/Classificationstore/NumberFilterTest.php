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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\NumberFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * @internal
 */
final class NumberFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    private const KEY_NAME = 'weight';

    private const GROUP_NAME = 'dimensions';

    public function testLessSettingBuildsRangeFilterOnKeyName(): void
    {
        $calls = $this->applyFilter(['setting' => 'less', 'to' => 10]);

        $this->assertCount(1, $calls);
        $this->assertSame(self::GROUP_NAME, $calls[0]['group']);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(NumberRangeFilter::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getField());
        $this->assertNull($filter->getMin());
        $this->assertSame(10, $filter->getMax());
    }

    public function testLessSettingWithNonNumericValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyFilter(['setting' => 'less', 'to' => 'abc']);
    }

    public function testMoreSettingBuildsRangeFilterOnKeyName(): void
    {
        $calls = $this->applyFilter(['setting' => 'more', 'from' => 3]);

        $this->assertCount(1, $calls);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(NumberRangeFilter::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getField());
        $this->assertSame(3, $filter->getMin());
        $this->assertNull($filter->getMax());
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

        $filter = new NumberFilter(
            $this->makeEmpty(GroupConfigRepositoryInterface::class, ['getById' => $group]),
            $this->makeEmpty(KeyGroupRelationRepositoryInterface::class, ['getByKeyGroupId' => $key])
        );

        $parameter = $this->getColumnFilterMock(
            'classificationStoreField',
            ColumnType::CLASSIFICATION_STORE_NUMBER->value,
            ['groupId' => 1, 'keyId' => 2, 'value' => $value]
        );

        $filter->apply($parameter, $query);

        return $calls;
    }
}
