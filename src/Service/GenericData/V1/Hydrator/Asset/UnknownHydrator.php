<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetSearchResultItem;
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

    public function hydrate(AssetSearchResultItem\Unknown $item): Unknown
    {
        return new Unknown(
            $this->iconService->getIconForAsset($item->getType(), $item->getMimeType()),
            $item->isHasChildren(),
            $item->getType(),
            $item->getKey(),
            $item->getMimeType(),
            $this->metaDataHydrator->hydrate($item->getMetadata()),
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