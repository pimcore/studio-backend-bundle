<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetPermissions;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

final class PermissionsHydrator implements PermissionsHydratorInterface
{
    public function hydrate(AssetPermissions $permissions): Permissions
    {
        return new Permissions(
            $permissions->isList(),
            $permissions->isView(),
            $permissions->isPublish(),
            $permissions->isDelete(),
            $permissions->isRename(),
            $permissions->isCreate(),
            $permissions->isSettings(),
            $permissions->isVersions(),
            $permissions->isProperties()
        );
    }
}