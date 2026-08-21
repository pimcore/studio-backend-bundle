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
    public function testGetAdvancedColumnConfigException(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
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
            group: ['test'],
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
        $this->assertNull($configs->getColumns()[0]->getGroupId());
        $this->assertNull($configs->getColumns()[0]->getKeyId());
    }

    /**
     * Regression pimcore/platform-version#296: a saved advanced-column config whose
     * simple field is missing `config.field` (e.g. the studio-ui-bundle bug where the
     * pre-selected first source field option was dropped on save) must not reach
     * `SimpleFieldConfig`'s constructor with an undefined array key — it should be
     * rejected as a 422 `InvalidArgumentException` naming the broken column instead of
     * causing an uncaught error that blanks the whole grid response.
     */
    public function testGetAdvancedColumnConfigSimpleFieldMissingFieldThrows(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => [],
                    ],
                ],
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Advanced column "name" (source field at index 0) is missing the required "field" config');

        $column->getAdvancedColumnConfig();
    }

    /**
     * The reported bug actually saves the advanced column without a `config` key at all
     * (`{"key": "simpleField"}`), not merely without `field` inside an empty `config`.
     */
    public function testGetAdvancedColumnConfigSimpleFieldMissingConfigThrows(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                    ],
                ],
            ],
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Advanced column "name" (source field at index 0) is missing the required "field" config');

        $column->getAdvancedColumnConfig();
    }

    public function testGetAdvancedColumnConfigRelationField(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
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
        $this->assertNull($configs->getColumns()[0]->getGroupId());
        $this->assertNull($configs->getColumns()[0]->getKeyId());
    }

    public function testGetAdvancedColumnConfigSimpleFieldWithClassificationStore(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'simpleField',
                        'config' => ['field' => 'csstore', 'groupId' => 5, 'keyId' => 7],
                    ],
                ],
            ],
        );

        $configs = $column->getAdvancedColumnConfig();

        $this->assertCount(1, $configs->getColumns());
        $this->assertInstanceOf(SimpleFieldConfig::class, $configs->getColumns()[0]);
        $this->assertSame('csstore', $configs->getColumns()[0]->getField());
        $this->assertSame(5, $configs->getColumns()[0]->getGroupId());
        $this->assertSame(7, $configs->getColumns()[0]->getKeyId());
    }

    public function testGetAdvancedColumnConfigRelationFieldWithClassificationStore(): void
    {
        $column = new Column(
            key: 'name',
            locale: 'de',
            type: 'ttest',
            group: ['test'],
            config: [
                'advancedColumns' => [
                    [
                        'key' => 'relationField',
                        'config' => [
                            'relation' => 'manufacturer',
                            'field' => 'csstore',
                            'groupId' => 5,
                            'keyId' => 7,
                        ],
                    ],
                ],
            ],
        );

        $configs = $column->getAdvancedColumnConfig();

        $this->assertCount(1, $configs->getColumns());
        $this->assertInstanceOf(RelationFieldConfig::class, $configs->getColumns()[0]);
        $this->assertSame('manufacturer', $configs->getColumns()[0]->getRelation());
        $this->assertSame('csstore', $configs->getColumns()[0]->getField());
        $this->assertSame(5, $configs->getColumns()[0]->getGroupId());
        $this->assertSame(7, $configs->getColumns()[0]->getKeyId());
    }

    public function testWidthDefaultsToNull(): void
    {
        $column = new Column('id', 'en', 'system.id', ['system'], []);

        $this->assertNull($column->getWidth());
    }

    public function testWidthIsSetWhenProvided(): void
    {
        $column = new Column('id', 'en', 'system.id', ['system'], [], 200);

        $this->assertSame(200, $column->getWidth());
    }

    public function testToArrayContainsWidth(): void
    {
        $column = new Column('id', 'en', 'system.id', ['system'], [], 150);

        $result = $column->toArray();

        $this->assertSame(150, $result['width']);
    }

    public function testToArrayContainsNullWidthWhenNotProvided(): void
    {
        $column = new Column('id', 'en', 'system.id', ['system'], []);

        $result = $column->toArray();

        $this->assertArrayHasKey('width', $result);
        $this->assertNull($result['width']);
    }
}
