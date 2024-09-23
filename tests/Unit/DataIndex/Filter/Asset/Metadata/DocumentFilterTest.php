<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\Asset\Metadata;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\DocumentFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class DocumentFilterTest extends Unit
{
    use ColumnFilterMockTrait;
    public function testIsExceptionIsThrownWhenFilterIsNotAIdOfDocuments(): void
    {
        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::never()
        ]);

        $columnFilterMock = $this->getColumnFilterMock('key', "type", "not_int");

        $stringFilter = new DocumentFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Filter value for document must be a integer (ID of the document)");
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDocumentFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', "type", 1);

        $queryMock = $this->makeEmpty(AssetQueryInterface::class, [
            'filterMetadata' => Expected::once(function ($key, $type, $value) {
                $this->assertSame("key", $key);
                $this->assertSame(FilterType::DOCUMENT->value, $type);
                $this->assertSame(1, $value);

                return $this->makeEmpty(AssetQueryInterface::class);
            })
        ]);

        $textAreaFilter = new DocumentFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}