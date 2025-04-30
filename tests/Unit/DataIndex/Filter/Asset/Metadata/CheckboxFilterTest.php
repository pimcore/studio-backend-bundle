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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\CheckboxFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class CheckboxFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNoABool(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never(),
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_bool');

        $stringFilter = new CheckboxFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for checkbox must be a boolean');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyCheckboxFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', true);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::CHECKBOX->value, $type);
                $this->assertSame(true, $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new CheckboxFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
