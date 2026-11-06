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

namespace Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Schema\MaintenanceModeState;

/**
 * @internal
 */
final readonly class MaintenanceModeStateHydrator implements MaintenanceModeStateHydratorInterface
{
    public function hydrate(bool $active): MaintenanceModeState
    {
        return new MaintenanceModeState(active: $active);
    }
}
