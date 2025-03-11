<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Schema;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\RelationFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\AdvancedColumnConfig\SimpleFieldConfig;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;

/**
 * @internal
 */
final class ColumnTest extends Unit
{
    public function testGetAdvancedColumnConfigException() : void
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
                    ['field' => 'name'],
                ]
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
                    ['field' => 'name', 'relation' => 'manufacturer'],
                ]
            ],
        );

        $configs = $column->getAdvancedColumnConfig();

        $this->assertCount(1, $configs->getColumns());
        $this->assertSame('name', $configs->getColumns()[0]->getField());
        $this->assertSame('manufacturer', $configs->getColumns()[0]->getRelation());
        $this->assertInstanceOf(RelationFieldConfig::class, $configs->getColumns()[0]);
    }

}