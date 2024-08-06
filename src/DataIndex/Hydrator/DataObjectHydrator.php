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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\DataObject\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;

final readonly class DataObjectHydrator implements DataObjectHydratorInterface
{
    public function __construct(
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
            $dataObject->getId(),
            $dataObject->getParentId(),
            $dataObject->getPath(),
            $dataObject->getUserOwner(),
            $dataObject->getUserModification(),
            $dataObject->getLocked(),
            $dataObject->isLocked(),
            $dataObject->getCreationDate(),
            $dataObject->getModificationDate()
        );
    }
}
