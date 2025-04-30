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

namespace Pimcore\Bundle\StudioBackendBundle\DataIndex\Hydrator\Asset;

use Pimcore\Bundle\GenericDataIndexBundle\Permission\AssetPermissions as SearchAssetPermissions;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetPermissions;

interface PermissionsHydratorInterface
{
    public function hydrate(SearchAssetPermissions $permissions): AssetPermissions;
}
