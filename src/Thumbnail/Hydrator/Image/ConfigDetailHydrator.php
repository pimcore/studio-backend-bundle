<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\Image;

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ImageThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ImageThumbnailSettings;
use Pimcore\Model\Asset\Image\Thumbnail\Config;

/**
 * @internal
 */
final readonly class ConfigDetailHydrator implements ConfigDetailHydratorInterface
{
    public function hydrate(Config $configuration): ImageThumbnailConfigDetail
    {
        $settings = new ImageThumbnailSettings(
            name: $configuration->getName(),
            description: $configuration->getDescription(),
            group: $configuration->getGroup(),
            format: $configuration->getFormat(),
            quality: $configuration->getQuality(),
            highResolution: $configuration->getHighResolution(),
            preserveColor: $configuration->isPreserveColor(),
            forceProcessICCProfiles: $configuration->isForceProcessICCProfiles(),
            preserveMetaData: $configuration->isPreserveMetaData(),
            rasterizeSVG: $configuration->isRasterizeSVG(),
            useCropBox: $configuration->isUseCropBox(),
            downloadable: $configuration->isDownloadable(),
            modificationDate: $configuration->getModificationDate(),
            creationDate: $configuration->getCreationDate(),
            filenameSuffix: $configuration->getFilenameSuffix(),
            preserveAnimation: $configuration->getPreserveAnimation()
        );

        return new ImageThumbnailConfigDetail(
            settings: $settings,
            writeable: $configuration->isWriteable(),
            medias: $this->hydrateMedias($configuration)
        );
    }

    private function hydrateMedias(Config $configuration): array
    {
        $medias = [];
        $items = $configuration->getItems();

        if (!empty($items)) {
            $medias['default'] = $items;
        }

        foreach ($configuration->getMedias() as $mediaQuery => $mediaItems) {
            $medias[$mediaQuery] = $mediaItems;
        }

        return $medias;
    }
}
