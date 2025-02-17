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
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameter;

/**
 * @internal
 */
interface UpdateConfigurationServiceInterface
{
    /**
     * @throws NotFoundException|InvalidArgumentException|ForbiddenException
     */
    public function updateAssetGridConfigurationById(ConfigurationParameter $configurationParams, int $id): void;

    /**
     * @throws NotFoundException|InvalidArgumentException|ForbiddenException
     */
    public function updateDataObjectGridConfigurationById(ConfigurationParameter $configurationParams, int $id): void;

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ForbiddenException
     */
    public function setAssetGridConfigurationAsFavorite(int $configurationId, int $folderId): void;

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ForbiddenException
     */
    public function setDataObjectGridConfigurationAsFavorite(int $configurationId, int $folderId): void;
}
