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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ConsoleDependencyMissingException;
use Pimcore\Tool\Console;

/**
 * @internal
 */
trait ConsoleExecutableTrait
{
    /**
     * @throws ConsoleDependencyMissingException
     */
    private function getExecutable(string $executable, string $module = 'Pimcore'): string
    {
        try {
            $consoleExecutable = Console::getExecutable($executable);

            if (!$consoleExecutable) {
                throw new ConsoleDependencyMissingException(
                    $executable,
                    $module
                );
            }

            return $consoleExecutable;
        } catch (Exception) {
            throw new ConsoleDependencyMissingException(
                $executable,
                $module
            );
        }
    }
}
