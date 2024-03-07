<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;

interface AssetServiceInterface
{
    public function handleAsset(int $id, Asset $data): \Pimcore\Model\Asset;
}