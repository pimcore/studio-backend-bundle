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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\Grid;

use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\UpdateConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface UpdateConfigurationServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function updateAssetGridConfigurationById(UpdateConfigurationParameter $configurationParams, int $id): void;

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ForbiddenException
     */
    public function setAssetGridConfigurationAsFavorite(int $configurationId, int $folderId): void;
}
