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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Schema\Grid;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Grid\ColumnSchema;

/**
 * @internal
 */
final class ColumnSchemaTest extends Unit
{
    public function testGettersReturnConstructorValues(): void
    {
        $column = new ColumnSchema('id', null, ['system'], 200);

        $this->assertSame('id', $column->getKey());
        $this->assertNull($column->getLocale());
        $this->assertSame(['system'], $column->getGroup());
        $this->assertSame(200, $column->getWidth());
    }

    public function testWidthDefaultsToNull(): void
    {
        $column = new ColumnSchema('id', 'de', ['system']);

        $this->assertNull($column->getWidth());
    }

    public function testToArrayContainsAllFields(): void
    {
        $column = new ColumnSchema('id', 'de', ['system'], 150);

        $this->assertSame(
            [
                'key' => 'id',
                'locale' => 'de',
                'group' => ['system'],
                'width' => 150,
            ],
            $column->toArray()
        );
    }

    public function testToArrayContainsNullWidthWhenNotProvided(): void
    {
        $column = new ColumnSchema('id', 'de', ['system']);

        $result = $column->toArray();

        $this->assertArrayHasKey('width', $result);
        $this->assertNull($result['width']);
    }
}
