<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetSearchResultItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Document;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Text;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Video;

interface AssetHydratorServiceInterface
{
    /**
     * @param AssetSearchResultItem $item
     * @return Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
     */
    public function hydrate(AssetSearchResultItem $item): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video;
}