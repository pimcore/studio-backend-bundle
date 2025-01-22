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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Version\Schema\AssetVersion;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionDetailServiceInterface;
use Pimcore\Model\Asset;

/**
 * @internal
 */
final readonly class AssetVersionHydrator implements AssetVersionHydratorInterface
{
    public function __construct(
        private DocumentServiceInterface $documentService,
        private VersionDetailServiceInterface $versionDetailService,
        private CustomMetadataVersionHydratorInterface $customMetadataVersionHydrator,
    ) {
    }

    /**
     * @throws Exception
     */
    public function hydrate(
        Asset $asset
    ): AssetVersion {
        if (
            $asset instanceof Asset\Document &&
            $asset->getMimeType() === MimeTypes::PDF->value &&
            $this->documentService->isScanningEnabled()
        ) {
            $this->documentService->validatePdfScanStatus(
                $asset
            );
        }

        return $this->hydrateAsset($asset);
    }

    private function hydrateAsset(Asset $asset): AssetVersion
    {
        return new AssetVersion(
            $asset->getType(),
            $asset->getFilename(),
            $asset->getCreationDate(),
            $asset->getModificationDate(),
            $this->versionDetailService->getAssetFileSize($asset) ?? $asset->getFileSize(),
            $asset->getMimeType(),
            $this->customMetadataVersionHydrator->hydrate($asset->getMetadata()),
            $this->versionDetailService->getDimensions($asset)
        );
    }
}
