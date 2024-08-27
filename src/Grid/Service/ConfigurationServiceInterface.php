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

use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;

/**
 * @internal
 */
interface ConfigurationServiceInterface
{
    public function getDefaultAssetGridConfiguration(): DetailedConfiguration;

    public function getAssetGridConfiguration(int $configurationId, int $folderId): DetailedConfiguration;

    /**
     * @return Configuration[]
     */
    public function getGridConfigurationsForFolder(int $folderId): array;
}
