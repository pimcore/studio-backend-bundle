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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Settings;

final class AdminSettingsEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.admin_settings';

    public function __construct(
        private readonly Settings $settings
    ) {
        parent::__construct($settings);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getSettings(): Settings
    {
        return $this->settings;
    }
}
