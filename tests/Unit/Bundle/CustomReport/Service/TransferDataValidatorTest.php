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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Bundle\CustomReport\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\TransferDataValidator;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class TransferDataValidatorTest extends Unit
{
    public function testAcceptsCompleteExportPayload(): void
    {
        $this->expectNotToPerformAssertions();

        $this->createValidator()->validate([
            'name' => 'Report',
            'sql' => '',
            'dataSourceConfig' => [['type' => 'sql', 'sql' => 'SELECT 1']],
            'columnConfiguration' => [[
                'name' => 'id',
                'display' => true,
                'export' => true,
                'order' => false,
                'label' => '',
                'action' => '',
                'id' => 'col-1',
                'width' => '',
                'displayType' => null,
                'filter' => 'text',
                'filter_drilldown' => null,
                'unknownField' => 'is ignored',
            ]],
            'niceName' => 'Report',
            'group' => '',
            'groupIconClass' => '',
            'iconClass' => '',
            'menuShortcut' => true,
            'reportClass' => '',
            'chartType' => 'pie',
            'pieColumn' => 'count',
            'pieLabelColumn' => null,
            'xAxis' => null,
            'yAxis' => ['a', 'b'],
            'shareGlobally' => false,
            'pagination' => true,
            'sharedUserNames' => ['editor'],
            'sharedRoleNames' => [],
            'unknownProperty' => 'is ignored',
        ]);
    }

    /**
     * @dataProvider malformedValues
     */
    public function testRejectsMalformedValues(array $data, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->createValidator()->validate($data);
    }

    public function testAcceptsDataSourceWithoutExplicitTypeWhenDefaultAdapterExists(): void
    {
        $this->expectNotToPerformAssertions();

        $this->createValidator()->validate(['dataSourceConfig' => [['sql' => 'SELECT 1']]]);
    }

    private function createValidator(): TransferDataValidator
    {
        $adapterService = $this->createMock(AdapterServiceInterface::class);
        $adapterService->method('hasAdapter')->willReturnCallback(
            static fn (string $type): bool => $type === 'sql'
        );

        return new TransferDataValidator($adapterService);
    }

    private static function column(array $overrides = []): array
    {
        return array_merge(['name' => 'id', 'display' => true, 'export' => true, 'order' => true], $overrides);
    }

    public static function malformedValues(): iterable
    {
        yield 'string property as array' => [
            ['niceName' => []],
            'Invalid value for "niceName": expected string, got array.',
        ];
        yield 'boolean property as string' => [
            ['menuShortcut' => 'yes'],
            'Invalid value for "menuShortcut": expected boolean, got string.',
        ];
        yield 'nullable property as integer' => [
            ['pieColumn' => 5],
            'Invalid value for "pieColumn": expected string or NULL, got integer.',
        ];
        yield 'array property as string' => [
            ['columnConfiguration' => 'id'],
            'Invalid value for "columnConfiguration": expected array, got string.',
        ];
        yield 'shared user names as map' => [
            ['sharedUserNames' => ['a' => 'editor']],
            'Invalid value for "sharedUserNames": expected a list of strings.',
        ];
        yield 'shared role names with non string item' => [
            ['sharedRoleNames' => ['admin', 3]],
            'Invalid value for "sharedRoleNames": expected a list of strings, got integer.',
        ];
        yield 'column configuration as map' => [
            ['columnConfiguration' => ['first' => ['name' => 'id']]],
            'Invalid value for "columnConfiguration": expected a list of objects.',
        ];
        yield 'column configuration entry as string' => [
            ['columnConfiguration' => ['id']],
            'Invalid value for "columnConfiguration[0]": expected object, got string.',
        ];
        yield 'column without display flag' => [
            ['columnConfiguration' => [self::column(), ['name' => 'path', 'export' => true, 'order' => true]]],
            'Invalid value for "columnConfiguration[1]": missing required field "display".',
        ];
        yield 'column without name' => [
            ['columnConfiguration' => [['display' => true, 'export' => true, 'order' => true]]],
            'Invalid value for "columnConfiguration[0]": missing required field "name".',
        ];
        yield 'column name as array' => [
            ['columnConfiguration' => [self::column(['name' => []])]],
            'Invalid value for "columnConfiguration[0].name": expected string, got array.',
        ];
        yield 'column display flag as string' => [
            ['columnConfiguration' => [self::column(), self::column(['name' => 'path', 'display' => 'yes'])]],
            'Invalid value for "columnConfiguration[1].display": expected boolean, got string.',
        ];
        yield 'column width as float' => [
            ['columnConfiguration' => [self::column(['width' => 1.5])]],
            'Invalid value for "columnConfiguration[0].width": expected integer or string or NULL, got double.',
        ];
        yield 'data source config entry as string' => [
            ['dataSourceConfig' => ['SELECT 1']],
            'Invalid value for "dataSourceConfig[0]": expected object, got string.',
        ];
        yield 'data source type as array' => [
            ['dataSourceConfig' => [['type' => []]]],
            'Invalid value for "dataSourceConfig[0].type": expected string, got array.',
        ];
        yield 'data source with unknown adapter type' => [
            ['dataSourceConfig' => [['type' => 'sql'], ['type' => 'graphql']]],
            'Invalid value for "dataSourceConfig[1].type": unknown adapter type "graphql".',
        ];
    }
}
