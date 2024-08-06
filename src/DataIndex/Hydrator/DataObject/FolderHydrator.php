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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\SearchResultItem\Folder;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

final readonly class FolderHydrator implements FolderHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(Folder $item): DataObjectFolder
    {
        return new DataObjectFolder(
            $item->getKey(),
            $item->getClassName(),
            $item->getType(),
            $item->isPublished(),
            $item->isHasChildren(),
            $item->isHasWorkflowWithPermissions(),
            $item->getFullPath(),
            $this->permissionsHydrator->hydrate($item->getPermissions()),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $this->iconService->getIconForDataObject($item->getType()),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate()
        );
    }
}
