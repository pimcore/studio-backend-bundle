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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Hydrator\Permissions;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\AssetContextPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions\SaveAssetContextPermissions;

/**
 * @internal
 */
final readonly class AssetContextPermissionHydrator implements AssetContextPermissionHydratorInterface
{
    public function hydrate(array $data): AssetContextPermissions
    {
        return new AssetContextPermissions(...$this->extractPermissions($data));
    }

    public function hydrateSavePermissions(array $data): SaveAssetContextPermissions
    {
        return new SaveAssetContextPermissions(...$this->extractPermissions($data));
    }

    private function extractPermissions(array $data): array
    {
        return [
            $data['hideAdd'] ?? false,
            $data['addUpload'] ?? true,
            $data['uploadNewVersion'] ?? true,
            $data['addUploadZip'] ?? true,
            $data['download'] ?? true,
            $data['downloadZip'] ?? true,
            $data['addFolder'] ?? true,
            $data['copy'] ?? true,
            $data['cut'] ?? true,
            $data['delete'] ?? true,
            $data['lock'] ?? true,
            $data['lockAndPropagate'] ?? true,
            $data['paste'] ?? true,
            $data['pasteCut'] ?? true,
            $data['refresh'] ?? true,
            $data['rename'] ?? true,
            $data['searchAndMove'] ?? true,
            $data['unlock'] ?? true,
            $data['unlockAndPropagate'] ?? true,
        ];
    }
}
