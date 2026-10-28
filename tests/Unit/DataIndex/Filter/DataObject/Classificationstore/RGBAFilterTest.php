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
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Filter\Basic\IntegerFilter;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\DataObject\Classificationstore\RGBAFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnType;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ClassificationStoreFilterTestTrait;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Filter\ColumnFilterMockTrait;

/**
 * @internal
 */
final class RGBAFilterTest extends Unit
{
    use ClassificationStoreFilterTestTrait;
    use ColumnFilterMockTrait;

    public function testOneFilterPerChannelIsBuiltOnTheResolvedKeyName(): void
    {
        $calls = $this->applyRGBAFilter(['r' => 1, 'g' => 2, 'b' => 3, 'a' => 0.5]);

        $this->assertCount(4, $calls);
        $fields = [];
        foreach ($calls as $call) {
            $this->assertSame(self::FIELD_NAME, $call['fieldName']);
            $this->assertSame(self::GROUP_NAME, $call['group']);
            $this->assertInstanceOf(IntegerFilter::class, $call['filter']);
            $fields[$call['filter']->getFieldName()] = $call['filter']->getSearchTerm();
        }

        $this->assertSame(
            [
                self::KEY_NAME . '.r' => 1,
                self::KEY_NAME . '.g' => 2,
                self::KEY_NAME . '.b' => 3,
                self::KEY_NAME . '.a' => 127,
            ],
            $fields
        );
    }

    public function testAnIncompleteColourThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->applyRGBAFilter(['r' => 1, 'g' => 2, 'b' => 3]);
    }

    private function applyRGBAFilter(array $value): array
    {
        return $this->applyFilter(RGBAFilter::class, ColumnType::CLASSIFICATION_STORE_RGBA->value, $value);
    }
}
