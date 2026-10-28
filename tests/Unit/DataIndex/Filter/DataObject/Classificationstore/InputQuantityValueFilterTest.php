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
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\InputQuantityValueFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class InputQuantityValueFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testValueAndUnitAreFilteredOnTheResolvedKeyName(): void
    {
        $calls = $this->applyInputQuantityValueFilter(['value' => '5', 'unitId' => 'kg']);

        $this->assertCount(2, $calls);
        foreach ($calls as $call) {
            $this->assertSame(self::FIELD_NAME, $call['fieldName']);
            $this->assertSame(self::GROUP_NAME, $call['group']);
            $this->assertInstanceOf(WildcardSearch::class, $call['filter']);
        }

        $this->assertSame(self::KEY_NAME . '.value', $calls[0]['filter']->getFieldName());
        $this->assertSame('5', $calls[0]['filter']->getSearchTerm());
        $this->assertSame(self::KEY_NAME . '.unitId', $calls[1]['filter']->getFieldName());
        $this->assertSame('kg', $calls[1]['filter']->getSearchTerm());
    }

    public function testAMissingUnitThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyInputQuantityValueFilter(['value' => '5']);
    }

    private function applyInputQuantityValueFilter(array $value): array
    {
        return $this->applyFilter(
            InputQuantityValueFilter::class,
            ColumnType::CLASSIFICATION_STORE_INPUT_QUANTITY_VALUE->value,
            $value
        );
    }
}
