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

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\SearchResultItem\Unknown as UnknownItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Unknown;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\PermissionsHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class UnknownHydrator implements UnknownHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(UnknownItem $item): Unknown
    {
        $unknown = new Unknown(
            $item->getId()
        );

        $unknown->setParentId($item->getParentId());
        $unknown->setPath($item->getPath());
        $unknown->setUserOwner($item->getUserOwner());
        $unknown->setUserModification($item->getUserModification());
        $unknown->setLocked($item->getLocked());
        $unknown->setIsLocked($item->isLocked());
        $unknown->setCreationDate($item->getCreationDate());
        $unknown->setModificationDate($item->getModificationDate());
        $unknown->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $unknown->setUserModification($item->getUserModification());

        // asset specific stuff
        $unknown->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $unknown->setHasChildren($item->isHasChildren());
        $unknown->setType($item->getType());
        $unknown->setFilename($item->getKey());
        $unknown->setMimeType($item->getMimeType());
        $unknown->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $unknown->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $unknown->setFullPath($item->getFullPath());

        return $unknown;
    }
}
