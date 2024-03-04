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
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\ArchiveHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\AudioHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\DocumentHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\FolderHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\ImageHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\TextHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\UnknownHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\VideoHydratorInterface;

final readonly class AssetHydratorService implements AssetHydratorServiceInterface
{
    public function __construct(
        private ArchiveHydratorInterface $archiveHydrator,
        private AudioHydratorInterface $audioHydrator,
        private DocumentHydratorInterface $documentHydrator,
        private FolderHydratorInterface $folderHydrator,
        private ImageHydratorInterface $imageHydrator,
        private TextHydratorInterface $textHydrator,
        private UnknownHydratorInterface $unknownHydrator,
        private VideoHydratorInterface $videoHydrator,
        private AssetHydratorInterface $assetHydrator
    ) {
    }

    /**
     * @param AssetSearchResultItem $item
     *
     * @return Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
     */
    public function hydrate(AssetSearchResultItem $item): Asset|Archive|Audio|Document|Folder|Image|Text|Unknown|Video
    {
        return match (true) {
            $item instanceof AssetSearchResultItem\Archive => $this->archiveHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Audio => $this->audioHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Document => $this->documentHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Folder => $this->folderHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Image => $this->imageHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Text => $this->textHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Unknown => $this->unknownHydrator->hydrate($item),
            $item instanceof AssetSearchResultItem\Video => $this->videoHydrator->hydrate($item),
            default => $this->assetHydrator->hydrate($item)
        };
    }
}
