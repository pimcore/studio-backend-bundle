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

namespace Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Schema\MaintenanceModeState;

/**
 * @internal
 */
interface MaintenanceModeServiceInterface
{
    public function getState(): MaintenanceModeState;

    /**
     * Activates maintenance mode and exempts the current request's session from it.
     *
     * @throws EnvironmentException|NoRequestException
     */
    public function activate(): MaintenanceModeState;

    public function deactivate(): MaintenanceModeState;
}
