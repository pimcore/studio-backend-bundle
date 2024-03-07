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
