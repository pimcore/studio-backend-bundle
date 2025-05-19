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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;

/**
 * @internal
 */
interface FavoriteServiceInterface
{
    public function setAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration;

    public function setDataObjectConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration;

    public function removeAssetConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration
    ): GridConfiguration;

    public function removeDataObjectConfigurationAsFavoriteForCurrentUser(
        GridConfiguration $gridConfiguration,
        int $folderId
    ): GridConfiguration;

    public function getFavoriteConfigurationForAssetFolder(int $folderId): ?GridConfiguration;

    public function getFavoriteConfigurationForDataObject(int $folderId, string $classId): ?GridConfiguration;
}
