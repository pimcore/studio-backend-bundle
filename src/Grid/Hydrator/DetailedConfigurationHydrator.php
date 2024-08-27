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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Entity\Grid\GridConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration as ConfigurationSchema;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;

/**
 * @internal
 */
final readonly class DetailedConfigurationHydrator implements DetailedConfigurationHydratorInterface
{
    public function hydrate(
        GridConfiguration $data,
        array $users,
        array $roles,
        bool $isFavorite
    ): DetailedConfiguration
    {
        return new DetailedConfiguration(
            name: $data->getName(),
            description: $data->getDescription(),
            shareGlobal: $data->isShareGlobal(),
            saveFilter: $data->saveFilter(),
            setAsFavorite: $isFavorite,
            sharedUsers: $users,
            sharedRoles: $roles,
            columns: $data->getColumns(),
            filter: $data->getFilter() ?? [],
            pageSize: $data->getPageSize()
        );
    }
}
