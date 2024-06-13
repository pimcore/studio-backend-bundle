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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Hydrator;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementUnsafeException;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\AssetVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\ImageVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Enum\PdfScanStatus;
use Pimcore\Model\Asset\Image;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class AssetVersionHydrator implements AssetVersionHydratorInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws Exception
     */
    public function hydrate(
        Asset $asset
    ): ImageVersion|AssetVersion {
        if ($asset instanceof Asset\Document &&
        $asset->getMimeType() === 'application/pdf') {
            $this->validatePdfStatus($asset);
        }

        if ($asset instanceof Image) {
            return $this->hydrateImage($asset);
        }

        $hydratedAsset = new AssetVersion(
            $asset->getFilename()
        );

        $this->eventDispatcher->dispatch(
            new AssetVersionEvent($hydratedAsset),
            AssetVersionEvent::EVENT_NAME
        );

        return $hydratedAsset;
    }

    private function hydrateImage(Image $image): ImageVersion
    {
        $hydratedImage = new ImageVersion(
            $image->getFilename(),
            $image->getCreationDate(),
            $image->getModificationDate(),
            $image->getFileSize(),
            $image->getMimeType(),
            $this->getImageDimensions($image)
        );

        $this->eventDispatcher->dispatch(
            new ImageVersionEvent($hydratedImage),
            ImageVersionEvent::EVENT_NAME
        );

        return $hydratedImage;
    }

    private function getImageDimensions(Image $image): Dimensions
    {
        try {
            $assetDimensions = $image->getDimensions();
        } catch (Exception) {
            return new Dimensions();
        }

        if (!$assetDimensions) {
            return new Dimensions();
        }

        return new Dimensions(
            $assetDimensions['width'],
            $assetDimensions['height']
        );
    }

    private function validatePdfStatus(Asset\Document $pdf): void
    {
        $status = $pdf->getScanStatus();

        if ($status === PdfScanStatus::SAFE) {
            return;
        }

        if ($status === Asset\Enum\PdfScanStatus::UNSAFE) {
            throw new ElementUnsafeException(
                $pdf->getId()
            );
        }

        throw new ElementProcessingNotCompletedException(
            $pdf->getId()
        );
    }
}
