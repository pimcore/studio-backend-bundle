<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Video;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\AssetSearchAdapterInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\AssetQuery;

final readonly class AssetSearchService implements AssetSearchServiceInterface
{
    public function __construct(private AssetSearchAdapterInterface $assetSearchAdapter)
    {
    }

    public function searchAssets(AssetQuery $assetQuery): AssetSearchResult
    {
        return $this->assetSearchAdapter->searchAssets($assetQuery);
    }

    public function getAssetById(int $id): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video|null
    {
        return $this->assetSearchAdapter->getAssetById($id);
    }
}
