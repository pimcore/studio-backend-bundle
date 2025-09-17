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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Schema;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\AdvancedColumnConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * @internal
 */
#[CoversClass(Column::class)]
#[UsesClass(AbstractApiException::class)]
#[UsesClass(InvalidArgumentException::class)]
#[UsesClass(SimpleFieldConfig::class)]
#[UsesClass(RelationFieldConfig::class)]
#[UsesClass(AdvancedColumnConfig::class)]
final class ColumnTest extends TestCase
{
    public function testGetAdvancedColumnConfigException(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: 'test',
            config: [],
        );

        $this->expectExceptionMessage('Advanced column config is not set');
        $this->expectException(InvalidArgumentException::class);

        $column->getAdvancedColumnConfig();
    }

    public function testGetAdvancedColumnConfigSimpleField(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: 'test',
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => [
                            'field' => 'name',
                        ],
                    ],
                ],
            ],
        );

        $configs = $column->getAdvancedColumnConfig();

        $this->assertCount(1, $configs->getColumns());
        $this->assertSame('name', $configs->getColumns()[0]->getField());
        $this->assertInstanceOf(SimpleFieldConfig::class, $configs->getColumns()[0]);
    }

    public function testGetAdvancedColumnConfigRelationField(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: 'test',
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'relationField',
                        'config' => ['field' => 'name', 'relation' => 'manufacturer'],
                    ],
                ],
            ],
        );

        $configs = $column->getAdvancedColumnConfig();

        $this->assertCount(1, $configs->getColumns());
        $this->assertSame('name', $configs->getColumns()[0]->getField());
        $this->assertSame('manufacturer', $configs->getColumns()[0]->getRelation());
        $this->assertInstanceOf(RelationFieldConfig::class, $configs->getColumns()[0]);
    }
}
