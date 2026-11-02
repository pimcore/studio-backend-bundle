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

namespace Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\MaintenanceMode\Schema\MaintenanceModeState;

/**
 * @internal
 */
final class MaintenanceModeStateEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.maintenance_mode_state';

    public function __construct(
        private readonly MaintenanceModeState $state
    ) {
        parent::__construct($state);
    }

    public function getState(): MaintenanceModeState
    {
        return $this->state;
    }
}
