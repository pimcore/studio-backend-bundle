<?php

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;
use Pimcore\Model\Asset\Image as CoreImage;

final readonly class ImageHydrator implements ImageHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService
    ) {
    }

    public function hydrate(CoreImage $item): Image
    {
        return new Image(
            $item->getWidth(),
            $item->getHeight(),
            '',
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            false,
            $item->getType(),
            $item->getFilename(),
            $item->getMimeType(),
            [],
            false,
            $item->getFullPath(),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate(),
            new Permissions()
        );
    }
}