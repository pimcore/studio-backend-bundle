<?php

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
