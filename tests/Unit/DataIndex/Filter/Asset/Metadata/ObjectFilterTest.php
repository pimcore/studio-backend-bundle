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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\ObjectFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class ObjectFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAInt(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never(),
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_int');

        $stringFilter = new ObjectFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for object must be a integer (ID of the object)');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyObjectFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 1);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::OBJECT->value, $type);
                $this->assertSame(1, $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new ObjectFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
