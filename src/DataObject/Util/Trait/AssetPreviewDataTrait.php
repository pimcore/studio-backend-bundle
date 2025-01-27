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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\ImageDownloadConfigParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\ResizeModes;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\ThumbnailInterface;
use Pimcore\Model\Asset\Video;

/**
 * @internal
 */
trait AssetPreviewDataTrait
{
    private function getSearchPreviewThumbnailPath(Asset $image): string
    {
        return $this->getSearchPreviewThumbnail($image)->getPath();
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getSearchPreviewThumbnail(Asset $asset): ThumbnailInterface
    {
        $searchPreviewConfig = $this->getSearchPreviewConfig();

        $configuration = new Config();
        $configuration->setFormat($searchPreviewConfig->getMimeType());
        $configuration->addItem(
            ResizeModes::SCALE_BY_WIDTH,
            ['width' => $searchPreviewConfig->getWidth(), 'height' => $searchPreviewConfig->getHeight()]
        );
        $configuration->setName(
            'pimcore-search-preview-' . $asset->getId() . '-' . md5(serialize($searchPreviewConfig))
        );

        return match (true) {
            $asset instanceof Image => $asset->getThumbnail($configuration),
            $asset instanceof Document, $asset instanceof Video => $asset->getImageThumbnail($configuration),
            default => throw new InvalidElementTypeException($asset->getType())
        };
    }

    private function getSearchPreviewConfig(): ImageDownloadConfigParameter
    {
        return new ImageDownloadConfigParameter(
            mimeType: MimeTypes::ORIGINAL->value,
            resizeMode: ResizeModes::SCALE_BY_WIDTH,
            width: 100,
            height: 100
        );
    }
}
