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
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\AssetVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Event\ImageVersionEvent;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\ImageVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionDetailServiceInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Image;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class AssetVersionHydrator implements AssetVersionHydratorInterface
{
    public function __construct(
        private DocumentServiceInterface $documentService,
        private EventDispatcherInterface $eventDispatcher,
        private VersionDetailServiceInterface $versionDetailService,
        private CustomMetadataVersionHydratorInterface $customMetadataVersionHydrator,
    ) {
    }

    /**
     * @throws Exception
     */
    public function hydrate(
        Asset $asset
    ): ImageVersion|AssetVersion {
        if (
            $asset instanceof Asset\Document &&
            $asset->getMimeType() === MimeTypes::PDF &&
            $this->documentService->isScanningEnabled()
        ) {
            $this->documentService->validatePdfScanStatus(
                $asset
            );
        }

        if ($asset instanceof Image) {
            return $this->hydrateImage($asset);
        }

        $hydratedAsset = new AssetVersion(
            fileName: $asset->getFilename(),
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
            $this->versionDetailService->getAssetFileSize($image) ?? $image->getFileSize(),
            $image->getMimeType(),
            $this->customMetadataVersionHydrator->hydrate($image->getMetadata()),
            $this->versionDetailService->getImageDimensions($image)
        );

        $this->eventDispatcher->dispatch(
            new ImageVersionEvent($hydratedImage),
            ImageVersionEvent::EVENT_NAME
        );

        return $hydratedImage;
    }
}
