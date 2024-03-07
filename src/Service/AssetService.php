<?php

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Element\DuplicateFullPathException;
use Pimcore\Model\Exception\NotFoundException;

final class AssetService implements AssetServiceInterface
{
    public function __construct(private readonly AssetResolverInterface $assetResolver)
    {
    }

    /**
     * @throws DuplicateFullPathException
     */
    public function handleAsset(int $id, Asset $data): \Pimcore\Model\Asset
    {
        $asset = $this->assetResolver->getById($id);

        if(!$asset) {
            throw new NotFoundException('Asset not found');
        }

        return $this->setAssetData($asset, $data);
    }


    /**
     * @throws DuplicateFullPathException
     */
    private function setAssetData(\Pimcore\Model\Asset $asset, Asset $data): \Pimcore\Model\Asset
    {
        $asset->setFilename($data->getFilename() ?: $asset->getFilename());
        $asset->save();
        return $asset;
    }
}