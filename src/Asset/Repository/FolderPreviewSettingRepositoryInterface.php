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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Asset\FolderPreviewSetting;

/**
 * @internal
 */
interface FolderPreviewSettingRepositoryInterface
{
    public function getByUserAndFolder(int $user, int $folderId): ?FolderPreviewSetting;

    public function save(FolderPreviewSetting $setting): FolderPreviewSetting;
}
