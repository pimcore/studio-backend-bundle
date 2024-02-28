<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Hydrator;

use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Asset\AssetSearchResult\AssetPermissions;
use Pimcore\Bundle\StudioApiBundle\Dto\Asset\Permissions;

interface PermissionsHydratorInterface
{
    public function hydrate(AssetPermissions $permissions): Permissions;
}