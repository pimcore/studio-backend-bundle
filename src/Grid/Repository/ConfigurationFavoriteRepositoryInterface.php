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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfigurationFavorite;

/**
 * @internal
 */
interface ConfigurationFavoriteRepositoryInterface
{
    public function getByUserAndAssetFolder(int $user, int $assetFolderId): ?GridConfigurationFavorite;

    public function getByUserAndDataObject(
        int $user,
        int $dataObjectFolderId,
        string $classId
    ): ?GridConfigurationFavorite;
}
