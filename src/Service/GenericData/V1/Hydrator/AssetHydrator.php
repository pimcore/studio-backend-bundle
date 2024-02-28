<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetSearchResultItem;
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
        return new Asset(
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            $item->isHasChildren(),
            $item->getType(),
            $item->getKey(),
            $item->getMimeType(),
            $this->metaDataHydrator->hydrate($item->getMetaData()),
            $item->isHasWorkflowWithPermissions(),
            $item->getFullPath(),
            $item->getId(),
            $item->getParentId(),
            $item->getPath(),
            $item->getUserOwner(),
            $item->getUserModification(),
            $item->getLocked(),
            $item->isLocked(),
            $item->getCreationDate(),
            $item->getModificationDate(),
            $this->permissionsHydrator->hydrate($item->getPermissions())
        );
    }
}
