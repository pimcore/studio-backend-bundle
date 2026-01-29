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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\Video;

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailSettings;
use Pimcore\Model\Asset\Video\Thumbnail\Config;

/**
 * @internal
 */
final readonly class ConfigDetailHydrator implements ConfigDetailHydratorInterface
{
    public function hydrate(Config $configuration): VideoThumbnailConfigDetail
    {
        $settings = new VideoThumbnailSettings(
            name: $configuration->getName(),
            description: $configuration->getDescription(),
            group: $configuration->getGroup(),
            videoBitrate: $configuration->getVideoBitrate(),
            audioBitrate: $configuration->getAudioBitrate(),
            modificationDate: $configuration->getModificationDate(),
            creationDate: $configuration->getCreationDate(),
            filenameSuffix: $configuration->getFilenameSuffix()
        );

        return new VideoThumbnailConfigDetail(
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
