<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service;

interface IconServiceInterface
{
    public function getIconForAsset(string $assetType, string $mimeType): string;
}