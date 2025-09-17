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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\FilterType;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Asset\Metadata\TextAreaFilter;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\SimpleColumnFilter;

#[CoversClass(TextAreaFilter::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(AbstractApiException::class)]
#[UsesClass(ColumnFilter::class)]
#[UsesClass(SimpleColumnFilter::class)]
/**
 * @internal
 */
final class TextAreaFilterTest extends TestCase
{
    use ColumnFilterMockTrait;

    public function testIsExceptionIsThrownWhenFilterIsNotAString(): void
    {
        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->never())->method('filterMetadata');

        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 123);

        $stringFilter = new TextAreaFilter();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Filter value for textarea must be a string');
        $stringFilter->apply($columnFilterMock, $queryMock);
    }

    public function testApplyTextAreaFilter(): void
    {
        $columnFilterMock = $this->getColumnFilterMock('key', 'type', 'value');

        $queryMock = $this->createMock(AssetQueryInterface::class);
        $queryMock->expects($this->once())
            ->method('filterMetadata')
            ->with('key', FilterType::TEXTAREA->value, 'value')
            ->willReturn($this->createMock(AssetQueryInterface::class));

        $textAreaFilter = new TextAreaFilter();
        $textAreaFilter->apply($columnFilterMock, $queryMock);
    }
}
