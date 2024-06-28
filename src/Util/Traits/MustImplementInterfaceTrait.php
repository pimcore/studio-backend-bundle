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

use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use function class_implements;
use function in_array;
use function sprintf;

/**
 * @internal
 */
trait MustImplementInterfaceTrait
{
    /**
     * @throws MustImplementInterfaceException
     */
    private function checkInterface(string $class, string $interface): void
    {
        $classInterfaces = class_implements($class, false);
        if (
            $classInterfaces === false ||
            !in_array($interface, $classInterfaces, true)
        ) {
            throw new MustImplementInterfaceException(
                sprintf('%s must implement %s', $class, $interface)
            );
        }
    }
}
