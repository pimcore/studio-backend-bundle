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

        (new TransferDataValidator())->validate([
            'name' => 'Report',
            'sql' => '',
            'dataSourceConfig' => [['type' => 'sql', 'sql' => 'SELECT 1']],
            'columnConfiguration' => [['name' => 'id']],
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

        (new TransferDataValidator())->validate($data);
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
    }
}
