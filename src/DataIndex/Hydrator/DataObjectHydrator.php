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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\SearchIndexAdapter\ElementLockServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;

final readonly class DataObjectHydrator implements DataObjectHydratorInterface
{
    public function __construct(
        private ElementLockServiceInterface $elementLockService,
        private IconServiceInterface $iconService,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(DataObjectSearchResultItem $dataObject): DataObject
    {
        return new DataObject(
            $dataObject->getKey(),
            $dataObject->getClassName(),
            $dataObject->getType(),
            $dataObject->isPublished(),
            $dataObject->isHasChildren(),
            $dataObject->isHasWorkflowWithPermissions(),
            $dataObject->getFullPath(),
            $this->permissionsHydrator->hydrate($dataObject->getPermissions()),
            $dataObject->getIndex(),
            $dataObject->getChildrenSortBy(),
            $dataObject->getChildrenSortOrder(),
            $dataObject->getId(),
            $dataObject->getParentId(),
            $dataObject->getPath(),
            $this->iconService->getIconForDataObject($dataObject),
            $dataObject->getUserOwner(),
            $dataObject->getUserModification(),
            $dataObject->getLocked(),
            $this->elementLockService->isElementLocked(
                $dataObject->getFullPath(),
                $dataObject->getElementType()->value,
                $dataObject->getLocked()
            ),
            $dataObject->getCreationDate(),
            $dataObject->getModificationDate()
        );
    }
}
