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

    /**
     * @throws NotFoundException
     */
    public function getDataObjectGridConfiguration(
        ?int $configurationId,
        int $folderId,
        string $classId
    ): DetailedConfiguration;

    public function getConfigurationsForAssetsByFolder(int $folderId): Collection;

    public function getConfigurationsForDataObjectsByClassId(string $classId): Collection;

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException
     */
    public function deleteAssetConfiguration(int $configurationId, int $folderId): void;

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function deleteDataObjectConfiguration(int $configurationId): void;
}
