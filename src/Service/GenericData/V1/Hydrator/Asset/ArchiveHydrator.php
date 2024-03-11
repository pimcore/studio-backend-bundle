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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Archive as ArchiveItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Archive;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class ArchiveHydrator implements ArchiveHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(ArchiveItem $item): Archive
    {
        $archive =  new Archive($item->getId());

        $archive->setParentId($item->getParentId());
        $archive->setPath($item->getPath());
        $archive->setUserOwner($item->getUserOwner());
        $archive->setUserModification($item->getUserModification());
        $archive->setLocked($item->getLocked());
        $archive->setIsLocked($item->isLocked());
        $archive->setCreationDate($item->getCreationDate());
        $archive->setModificationDate($item->getModificationDate());
        $archive->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $archive->setUserModification($item->getUserModification());

        // asset specific stuff
        $archive->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $archive->setHasChildren($item->isHasChildren());
        $archive->setType($item->getType());
        $archive->setFilename($item->getKey());
        $archive->setMimeType($item->getMimeType());
        $archive->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $archive->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $archive->setFullPath($item->getFullPath());

        return $archive;
    }
}
