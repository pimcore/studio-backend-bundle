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

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Model\Asset as CoreAsset;
use Pimcore\Model\Exception\NotFoundException;

final readonly class AssetService implements AssetServiceInterface
{
    public function __construct(private AssetResolverInterface $assetResolver)
    {
    }

    public function processAsset(int $id, Asset $data): CoreAsset
    {
        $asset = $this->assetResolver->getById($id);

        if(!$asset) {
            throw new NotFoundException('Asset not found');
        }

        return $asset;
    }
}