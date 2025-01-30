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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\Preview;

use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\AssetSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Document;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\Asset\Video;

/**
 * @internal
 */
final readonly class AssetHydrator implements AssetHydratorInterface
{
    use ElementProviderTrait;

    public function __construct(
        private UserServiceInterface $userService,
        private ThumbnailServiceInterface $thumbnailService
    ) {
    }

    public function hydrate(Asset $asset): AssetSearchPreview
    {
        return new AssetSearchPreview(
            $asset->getMimeType(),
            $this->getThumbnailPath($asset),
            $asset->getId(),
            $this->getElementType($asset),
            $asset->getType(),
            $asset->getUserOwner(),
            $asset->getUserOwner() !== null ?
                $this->userService->getUserNameById($asset->getUserOwner()) :
                null,
            $asset->getUserModification(),
            $asset->getUserModification() !== null ?
                $this->userService->getUserNameById($asset->getUserModification()) :
                null,
            $asset->getCreationDate(),
            $asset->getModificationDate()
        );
    }

    private function getThumbnailPath(Asset $asset): ?string
    {
        return match (true) {
            $asset instanceof Image => $this->thumbnailService->getImagePreviewThumbnail($asset)->getPath(),
            $asset instanceof Video, $asset instanceof Document =>
                $this->thumbnailService->getAssetImagePreviewThumbnail($asset)->getPath(),
            default => null,
        };
    }
}
