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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IntegerFilter;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\NumberRangeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\NumberFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class NumberFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testIsSettingBuildsIntegerFilterOnKeyName(): void
    {
        $calls = $this->applyNumberFilter(['setting' => 'is', 'is' => 5]);

        $this->assertCount(1, $calls);
        $this->assertSame(self::FIELD_NAME, $calls[0]['fieldName']);
        $this->assertSame(self::GROUP_NAME, $calls[0]['group']);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(IntegerFilter::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getFieldName());
        $this->assertSame(5, $filter->getSearchTerm());
    }

    public function testLessSettingBuildsRangeFilterOnKeyName(): void
    {
        $filter = $this->applyAndGetRangeFilter(['setting' => 'less', 'to' => 10]);

        $this->assertNull($filter->getMin());
        $this->assertSame(10, $filter->getMax());
    }

    public function testMoreSettingBuildsRangeFilterOnKeyName(): void
    {
        $filter = $this->applyAndGetRangeFilter(['setting' => 'more', 'from' => 3]);

        $this->assertSame(3, $filter->getMin());
        $this->assertNull($filter->getMax());
    }

    public function testBetweenSettingBuildsRangeFilterOnKeyName(): void
    {
        $filter = $this->applyAndGetRangeFilter(['setting' => 'between', 'from' => 3, 'to' => 10]);

        $this->assertSame(3, $filter->getMin());
        $this->assertSame(10, $filter->getMax());
    }

    public function testNumericStringsAreCastToNumbers(): void
    {
        $filter = $this->applyAndGetRangeFilter(['setting' => 'between', 'from' => '3', 'to' => '10.5']);

        $this->assertSame(3, $filter->getMin());
        $this->assertSame(10.5, $filter->getMax());
    }

    public function testNumericStringIsCastForIsSetting(): void
    {
        $calls = $this->applyNumberFilter(['setting' => 'is', 'is' => '7']);

        $this->assertSame(7, $calls[0]['filter']->getSearchTerm());
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testInvalidValuesThrow(array $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyNumberFilter($value);
    }

    public static function invalidValueProvider(): array
    {
        return [
            'less non numeric' => [['setting' => 'less', 'to' => 'abc']],
            'less missing' => [['setting' => 'less']],
            'more non numeric' => [['setting' => 'more', 'from' => 'abc']],
            'is non numeric' => [['setting' => 'is', 'is' => 'abc']],
            'between missing to' => [['setting' => 'between', 'from' => 3]],
            'unknown setting' => [['setting' => 'around', 'is' => 3]],
        ];
    }

    private function applyAndGetRangeFilter(array $value): NumberRangeFilter
    {
        $calls = $this->applyNumberFilter($value);

        $this->assertCount(1, $calls);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(NumberRangeFilter::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getField());

        return $filter;
    }

    private function applyNumberFilter(array $value): array
    {
        return $this->applyFilter(NumberFilter::class, ColumnType::CLASSIFICATION_STORE_NUMBER->value, $value);
    }
}
