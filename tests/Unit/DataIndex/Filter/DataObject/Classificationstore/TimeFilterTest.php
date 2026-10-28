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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\FieldType\TimeFilter as GDITimeFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\TimeFilter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class TimeFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testRangeFilterIsBuiltOnTheResolvedKeyName(): void
    {
        $calls = $this->applyFilter(
            TimeFilter::class,
            ColumnType::CLASSIFICATION_STORE_TIME->value,
            ['from' => '08:15', 'to' => '17:45']
        );

        $this->assertCount(1, $calls);
        $this->assertSame(self::FIELD_NAME, $calls[0]['fieldName']);
        $this->assertSame(self::GROUP_NAME, $calls[0]['group']);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(GDITimeFilter::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getField());
        $this->assertSame('08:15', $filter->getStartTime());
        $this->assertSame('17:45', $filter->getEndTime());
    }
}
