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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\CheckboxFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\CheckboxFilter
 */
final class CheckboxFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNoABool(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_bool');

        $stringFilter = new CheckboxFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for checkbox must be a boolean');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyCheckboxFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', true);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with('key', FilterType::CHECKBOX->value, true)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new CheckboxFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
