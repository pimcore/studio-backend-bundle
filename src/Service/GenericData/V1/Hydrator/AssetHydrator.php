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

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\SearchResult\AssetSearchResultItem;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset\MetaDataHydratorInterface;
use Pimcore\Bundle\StudioApiBundle\Service\IconServiceInterface;

final readonly class AssetHydrator implements AssetHydratorInterface
{
    public function __construct(
        private IconServiceInterface $iconService,
        private MetaDataHydratorInterface $metaDataHydrator,
        private PermissionsHydratorInterface $permissionsHydrator
    ) {
    }

    public function hydrate(AssetSearchResultItem $item): Asset
    {
        $asset = new Asset($item->getId());
        // parent element stuff
        $asset->setParentId($item->getParentId());
        $asset->setPath($item->getPath());
        $asset->setUserOwner($item->getUserOwner());
        $asset->setUserModification($item->getUserModification());
        $asset->setLocked($item->getLocked());
        $asset->setIsLocked($item->isLocked());
        $asset->setCreationDate($item->getCreationDate());
        $asset->setModificationDate($item->getModificationDate());
        $asset->setPermissions($this->permissionsHydrator->hydrate($item->getPermissions()));
        $asset->setUserModification($item->getUserModification());

        // asset specific stuff
        $asset->setIconName($this->iconService->getIconForAsset($item->getType(), $item->getMimeType()));
        $asset->setHasChildren($item->isHasChildren());
        $asset->setType($item->getType());
        $asset->setFilename($item->getKey());
        $asset->setMimeType($item->getMimeType());
        $asset->setMetaData($this->metaDataHydrator->hydrate($item->getMetaData()));
        $asset->setWorkflowWithPermissions($item->isHasWorkflowWithPermissions());
        $asset->setFullPath($item->getFullPath());

        return $asset;
    }
}
