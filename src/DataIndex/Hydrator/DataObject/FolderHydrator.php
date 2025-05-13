<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\SearchResultItem\Folder;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\ElementLockServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Type\DataObjectFolder;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

/**
 * @internal
 */
final readonly class FolderHydrator implements FolderHydratorInterface
{
    public function __construct(
        private ElementLockServiceInterface $elementLockService,
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
            $item->getIndex(),
            $item->getChildrenSortBy(),
            $item->getChildrenSortOrder(),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $this->iconService->getIconForDataObject($item),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $this->elementLockService->isElementLocked(
                $item->getFullPath(),
                $item->getElementType()->value,
                $item->getLocked()
            ),
            $item->getCreationDate(),
            $item->getModificationDate()
        );
    }
}
