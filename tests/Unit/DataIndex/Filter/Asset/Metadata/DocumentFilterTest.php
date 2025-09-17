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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\DocumentFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;

/**
 * @internal
 */
#[CoversClass(DocumentFilter::class)]
#[UsesClass(ColumnFilter::class)]
#[UsesClass(SimpleColumnFilter::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(AbstractApiException::class)]
final class DocumentFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAIdOfDocuments(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'not_int');

        $stringFilter = new DocumentFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for document must be a integer (ID of the document)');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyDocumentFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 1);

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with('key', FilterType::DOCUMENT->value, 1)
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new DocumentFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
