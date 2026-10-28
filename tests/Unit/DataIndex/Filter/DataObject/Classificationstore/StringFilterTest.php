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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\WildcardSearch;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\StringFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class StringFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testFilterIsBuiltOnTheResolvedKeyName(): void
    {
        $calls = $this->applyFilter(
            StringFilter::class,
            ColumnType::CLASSIFICATION_STORE_STRING->value,
            'needle'
        );

        $this->assertCount(1, $calls);
        $this->assertSame(self::FIELD_NAME, $calls[0]['fieldName']);
        $this->assertSame(self::GROUP_NAME, $calls[0]['group']);
        $filter = $calls[0]['filter'];
        $this->assertInstanceOf(WildcardSearch::class, $filter);
        $this->assertSame(self::KEY_NAME, $filter->getFieldName());
        $this->assertSame('needle', $filter->getSearchTerm());
    }

    public function testNonStringValueThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyFilter(StringFilter::class, ColumnType::CLASSIFICATION_STORE_STRING->value, ['needle']);
    }
}
