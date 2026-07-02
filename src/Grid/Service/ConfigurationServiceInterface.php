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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;

/**
 * @internal
 */
interface ConfigurationServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getAssetGridConfiguration(?int $configurationId, int $folderId): DetailedConfiguration;

    public function getAssetSearchConfiguration(): DetailedConfiguration;

    /**
     * @throws NotFoundException
     */
    public function getDataObjectSearchConfiguration(?string $classId): DetailedConfiguration;

    /**
     * @throws NotFoundException
     */
    public function getDataObjectGridConfiguration(
        ?int $configurationId,
        int $folderId,
        string $classId
    ): DetailedConfiguration;

    public function getConfigurationsForAssets(): Collection;

    public function getConfigurationsForDataObjectsByClassId(string $classId): Collection;

    public function getGlobalConfigurationsForDataObjectsByClassId(string $classId): Collection;

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    public function deleteAssetConfiguration(int $configurationId): void;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function deleteDataObjectConfiguration(int $configurationId): void;
}
