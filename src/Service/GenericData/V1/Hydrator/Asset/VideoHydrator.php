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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Video as VideoItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Video;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class VideoHydrator implements VideoHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(VideoItem $item): Video
    {
        $video = new Video($item->getId());

        $video->setParentId($item->getParentId());
        $video->setPath($item->getPath());
        $video->setUserOwner($item->getUserOwner());
        $video->setUserModification($item->getUserModification());
        $video->setLocked($item->getLocked());
        $video->setIsLocked($item->isLocked());
        $video->setCreationDate($item->getCreationDate());
        $video->setModificationDate($item->getModificationDate());
        $video->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $video->setUserModification($item->getUserModification());

        // asset specific stuff
        $video->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $video->setHasChildren($item->isHasChildren());
        $video->setType($item->getType());
        $video->setFilename($item->getKey());
        $video->setMimeType($item->getMimeType());
        $video->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $video->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $video->setFullPath($item->getFullPath());

        $video->setDuration($item->getDuration());

        return $video;
    }
}
