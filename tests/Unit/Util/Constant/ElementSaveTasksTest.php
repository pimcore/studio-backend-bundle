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

    /**
     * During data binding the element still carries its old published state, so core's fallback
     * (omit the mandatory check while unpublished) must be overridden for requests that publish
     * or unpublish; null leaves the fallback untouched.
     *
     * @dataProvider resolveOmitMandatoryCheckForBindingProvider
     */
    public function testResolveOmitMandatoryCheckForBinding(?string $task, mixed $published, ?bool $expected): void
    {
        $this->assertSame($expected, ElementSaveTasks::resolveOmitMandatoryCheckForBinding($task, $published));
    }

    public static function resolveOmitMandatoryCheckForBindingProvider(): array
    {
        return [
            'publish task enforces the check' => [ElementSaveTasks::PUBLISH->value, null, false],
            'published payload flag enforces the check' => [null, true, false],
            'unpublished payload flag omits the check' => [null, false, true],
            'autoSave omits the check' => [ElementSaveTasks::AUTOSAVE->value, null, true],
            'version omits the check' => [ElementSaveTasks::VERSION->value, null, true],
            'unpublish task omits the check' => [ElementSaveTasks::UNPUBLISH->value, null, true],
            'version wins over published payload flag' => [ElementSaveTasks::VERSION->value, true, true],
            'save keeps the core fallback' => [ElementSaveTasks::SAVE->value, null, null],
            'no task keeps the core fallback' => [null, null, null],
            'unknown task keeps the core fallback' => ['draft', null, null],
            'non-boolean published value keeps the core fallback' => [null, 'true', null],
        ];
    }
}
