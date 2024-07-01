<?php

namespace Pimcore\Bundle\StudioBackendBundle\Util\Traits;

trait TempFilePathTrait
{
    private const ID_PLACEHOLDER = '{id}';
    public function getTempFilePath(int $id, string $path): string
    {
        return str_replace(self::ID_PLACEHOLDER, (string)$id, $path);
    }
}