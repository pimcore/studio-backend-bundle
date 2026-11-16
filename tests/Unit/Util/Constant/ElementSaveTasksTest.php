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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Util\Constant;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementSaveTasks;

/**
 * @internal
 */
final class ElementSaveTasksTest extends Unit
{
    /**
     * Must match the tasks for which ElementSaveService calls setOmitMandatoryCheck(true),
     * so that validation during data binding and validation during save agree.
     *
     * @dataProvider omitsMandatoryCheckProvider
     */
    public function testOmitsMandatoryCheck(?string $task, bool $expected): void
    {
        $this->assertSame($expected, ElementSaveTasks::omitsMandatoryCheck($task));
    }

    public static function omitsMandatoryCheckProvider(): array
    {
        return [
            'autoSave' => [ElementSaveTasks::AUTOSAVE->value, true],
            'version' => [ElementSaveTasks::VERSION->value, true],
            'unpublish' => [ElementSaveTasks::UNPUBLISH->value, true],
            'save' => [ElementSaveTasks::SAVE->value, false],
            'publish' => [ElementSaveTasks::PUBLISH->value, false],
            'no task' => [null, false],
            'unknown task' => ['draft', false],
        ];
    }
}
