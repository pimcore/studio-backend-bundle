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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Repository;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;

/**
 * @internal
 */
interface ConfigurationRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id): GridConfiguration;

    public function create(GridConfiguration $configuration): GridConfiguration;

    public function update(GridConfiguration $configuration): GridConfiguration;

    public function clearShares(GridConfiguration $configuration): GridConfiguration;

    /**
     * @return GridConfiguration[]
     */
    public function getByAssetFolderId(int $folderId): array;
}
