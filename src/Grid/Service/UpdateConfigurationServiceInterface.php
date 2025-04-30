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
