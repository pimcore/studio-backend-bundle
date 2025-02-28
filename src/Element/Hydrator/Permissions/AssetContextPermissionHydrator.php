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
