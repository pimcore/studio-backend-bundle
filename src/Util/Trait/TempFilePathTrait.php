<?php

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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

trait TempFilePathTrait
{
    private const ID_PLACEHOLDER = '{id}';

    public function getTempFilePath(mixed $id, string $path): string
    {
        return str_replace(self::ID_PLACEHOLDER, (string)$id, $path);
    }

    public function getTempFilePathFromName(mixed $id, string $name): string
    {
        return str_replace(
            self::ID_PLACEHOLDER,
            (string)$id,
            PIMCORE_SYSTEM_TEMP_DIRECTORY . '/' . $name
        );
    }

    public function getTempFileName(mixed $id, string $fileName): string
    {
        return str_replace(self::ID_PLACEHOLDER, (string)$id, $fileName);
    }
}
