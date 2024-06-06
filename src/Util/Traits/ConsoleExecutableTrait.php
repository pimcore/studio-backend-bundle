<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\ConsoleDependencyMissingException;
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
