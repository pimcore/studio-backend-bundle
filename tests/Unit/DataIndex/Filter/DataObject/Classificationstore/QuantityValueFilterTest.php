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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\QuantityValueFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class QuantityValueFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testIsSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyQuantityFilter(['setting' => 'is', 'is' => 5, 'unitId' => 'kg']);

        $this->assertCalls($calls);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(NumberFilter::class, $filter);
        $this->assertSame(self::KEY_NAME . '.value', $filter->getFieldName());
        $this->assertSame(5, $filter->getSearchTerm());
    }

    public function testLessSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyQuantityFilter(['setting' => 'less', 'to' => 10, 'unitId' => 'kg']);

        $this->assertCalls($calls);
        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertNull($filter->getMin());
        $this->assertSame(10, $filter->getMax());
    }

    public function testMoreSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyQuantityFilter(['setting' => 'more', 'from' => 3, 'unitId' => 'kg']);

        $this->assertCalls($calls);
        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertSame(3, $filter->getMin());
        $this->assertNull($filter->getMax());
    }

    public function testBetweenSettingUsesKeyNameValueField(): void
    {
        $calls = $this->applyQuantityFilter(['setting' => 'between', 'from' => 3, 'to' => 10, 'unitId' => 'kg']);

        $this->assertCalls($calls);
        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertSame(3, $filter->getMin());
        $this->assertSame(10, $filter->getMax());
    }

    public function testNumericStringsAreCastToNumbers(): void
    {
        $calls = $this->applyQuantityFilter(
            ['setting' => 'between', 'from' => '3', 'to' => '10.5', 'unitId' => 'kg']
        );

        $filter = $this->assertRangeFilter($calls[0]['filter']);
        $this->assertSame(3, $filter->getMin());
        $this->assertSame(10.5, $filter->getMax());
    }

    public function testNumericStringIsCastForIsSetting(): void
    {
        $calls = $this->applyQuantityFilter(['setting' => 'is', 'is' => '7', 'unitId' => 'kg']);

        $this->assertSame(7, $calls[0]['filter']->getSearchTerm());
    }

    /**
     * @dataProvider invalidValueProvider
     */
    public function testInvalidValuesThrow(array $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyQuantityFilter($value + ['unitId' => 'kg']);
    }

    public static function invalidValueProvider(): array
    {
        return [
            'unknown setting' => [['setting' => 'around', 'is' => 3]],
            'non string setting' => [['setting' => 5]],
            'missing setting' => [['is' => 3]],
            'is missing' => [['setting' => 'is']],
            'is non numeric' => [['setting' => 'is', 'is' => 'abc']],
            'less missing' => [['setting' => 'less']],
            'less non numeric' => [['setting' => 'less', 'to' => 'abc']],
            'more missing' => [['setting' => 'more']],
            'more non numeric' => [['setting' => 'more', 'from' => 'abc']],
            'between missing from' => [['setting' => 'between', 'to' => 10]],
            'between missing to' => [['setting' => 'between', 'from' => 3]],
            'between missing both' => [['setting' => 'between']],
        ];
    }

    private function assertCalls(array $calls): void
    {
        $this->assertCount(2, $calls);
        foreach ($calls as $call) {
            $this->assertSame(self::FIELD_NAME, $call['fieldName']);
            $this->assertSame(self::GROUP_NAME, $call['group']);
        }

        $unitFilter = $calls[1]['filter'];
        $this->assertInstanceOf(WildcardSearch::class, $unitFilter);
        $this->assertSame(self::KEY_NAME . '.unitId', $unitFilter->getFieldName());
        $this->assertSame('kg', $unitFilter->getSearchTerm());
    }

    private function assertRangeFilter(mixed $filter): NumberRangeFilter
    {
        $this->assertInstanceOf(NumberRangeFilter::class, $filter);
        $this->assertSame(self::KEY_NAME . '.value', $filter->getField());

        return $filter;
    }

    private function applyQuantityFilter(array $value): array
    {
        return $this->applyFilter(
            QuantityValueFilter::class,
            ColumnType::CLASSIFICATION_STORE_QUANTITY_VALUE->value,
            $value
        );
    }
}
