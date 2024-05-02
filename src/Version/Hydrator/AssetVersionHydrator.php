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
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetType\ImageVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\Dimensions;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;

/**
 * @internal
 */
final class AssetVersionHydrator implements AssetVersionHydratorInterface
{
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

        return new AssetVersion(
            $asset->getFilename(),
            $this->getTemporaryFile($asset),
        );
    }

    private function hydrateImage(Image $image): ImageVersion
    {
        return new ImageVersion(
            $image->getFilename(),
            $this->getTemporaryFile($image),
            $image->getCreationDate(),
            $image->getModificationDate(),
            $image->getFileSize(),
            $image->getMimeType(),
            $this->getImageDimensions($image)
        );
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

    private function getTemporaryFile(Asset $asset): ?string
    {
        try {
            $temporaryFile = $asset->getTemporaryFile();
        } catch (Exception) {
            return null;
        }

        return $temporaryFile;
    }

    private function validatePdfStatus(Asset\Document $pdf): void
    {
        $status = $pdf->getScanStatus();

        if ($status !== Asset\Enum\PdfScanStatus::SAFE) {
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
}
