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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Event\PreUpdate;

use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\UpdateSettings;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched before the admin settings are persisted. Subscribers can read the full update
 * payload, including its namespaced additional attributes, to persist bundle-specific data.
 */
final class AdminSettingsEvent extends Event
{
    public const string EVENT_NAME = 'pre_update.admin_settings';

    public function __construct(
        private readonly UpdateSettings $updateSettings
    ) {
    }

    public function getUpdateSettings(): UpdateSettings
    {
        return $this->updateSettings;
    }
}
