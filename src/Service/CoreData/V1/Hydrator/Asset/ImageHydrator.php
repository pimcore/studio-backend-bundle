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

namespace Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator\Asset;

use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Image;

use Pimcore\Bundle\StudioApiBundle\Service\CoreData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;
use Pimcore\Model\Asset\Image as CoreImage;

final readonly class ImageHydrator implements ImageHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
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
            $this->permissionsHydrator->hydrate($item)
        );
    }
}
