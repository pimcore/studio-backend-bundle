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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Folder as FolderItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Folder;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class FolderHydrator implements FolderHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(FolderItem $item): Folder
    {
        $folder = new Folder($item->getId());

        $folder->setParentId($item->getParentId());
        $folder->setPath($item->getPath());
        $folder->setUserOwner($item->getUserOwner());
        $folder->setUserModification($item->getUserModification());
        $folder->setLocked($item->getLocked());
        $folder->setIsLocked($item->isLocked());
        $folder->setCreationDate($item->getCreationDate());
        $folder->setModificationDate($item->getModificationDate());
        $folder->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $folder->setUserModification($item->getUserModification());

        // asset specific stuff
        $folder->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $folder->setHasChildren($item->isHasChildren());
        $folder->setType($item->getType());
        $folder->setFilename($item->getKey());
        $folder->setMimeType($item->getMimeType());
        $folder->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $folder->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $folder->setFullPath($item->getFullPath());

        return $folder;
    }
}
