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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Archive as ArchiveItem;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\Type\Archive;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

final readonly class ArchiveHydrator implements ArchiveHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(ArchiveItem $item): Archive
    {
        return new Archive(
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
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
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate(),
        );
    }
}
