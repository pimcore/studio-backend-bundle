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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Image as ImageItem;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Image;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

final readonly class ImageHydrator implements ImageHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(ImageItem $item): Image
    {
        return new Image(
            'muhFormat', // TODO: Implement format
            $item->getWidth(),
            $item->getHeight(),
            false, // TODO: Implement isVectorGraphic
            false,// TODO: Implement isAnimated
            $item->getThumbnail(),
            $item->isHasChildren(),
            $item->getType(),
            $item->getKey(),
            $item->getMimeType(),
            !empty($item->getMetaData()),
            $item->isHasWorkflowWithPermissions(),
            $item->getFullPath(),
            $this->permissionsHydrator->hydrate($item->getPermissions()),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate(),
        );
    }
}
