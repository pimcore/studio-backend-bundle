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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\AssetFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 * @covers \Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\AssetFilter
 */
final class AssetFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAnIdOfAssets(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_at');

        $stringFilter = new AssetFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for asset must be a integer (ID of the asset)');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyAssetFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 1);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with('key', FilterType::ASSET->value, 1)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new AssetFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
