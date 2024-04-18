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

use Pimcore\Bundle\StudioApiBundle\Response\Asset;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Response\Asset\Video;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;

interface AssetSearchServiceInterface
{
    public function searchAssets(AssetQuery $assetQuery): AssetSearchResult;

    public function getAssetById(int $id): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video|null;
}
