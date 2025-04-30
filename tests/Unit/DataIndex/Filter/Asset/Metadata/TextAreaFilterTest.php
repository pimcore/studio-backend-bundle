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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\TextAreaFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class TextAreaFilterTest extends Unit
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never(),
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 123);

        $stringFilter = new TextAreaFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for textarea must be a string');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyTextAreaFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'value');

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) {
                $this->assertSame('key', $key);
                $this->assertSame(FilterType::TEXTAREA->value, $type);
                $this->assertSame('value', $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            }),
        ]);

        $textAreaFilter = new TextAreaFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
