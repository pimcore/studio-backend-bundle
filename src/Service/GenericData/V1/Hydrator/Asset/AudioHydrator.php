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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Audio as AudioItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Audio;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class AudioHydrator implements AudioHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(AudioItem $item): Audio
    {
        $audio = new Audio($item->getId());
        // parent element stuff
        $audio->setParentId($item->getParentId());
        $audio->setPath($item->getPath());
        $audio->setUserOwner($item->getUserOwner());
        $audio->setUserModification($item->getUserModification());
        $audio->setLocked($item->getLocked());
        $audio->setIsLocked($item->isLocked());
        $audio->setCreationDate($item->getCreationDate());
        $audio->setModificationDate($item->getModificationDate());
        $audio->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $audio->setUserModification($item->getUserModification());

        // asset specific stuff
        $audio->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $audio->setHasChildren($item->isHasChildren());
        $audio->setType($item->getType());
        $audio->setFilename($item->getKey());
        $audio->setMimeType($item->getMimeType());
        $audio->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $audio->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $audio->setFullPath($item->getFullPath());

        return $audio;
    }
}
