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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class CustomSettingsEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.asset_custom_settings';

    public function __construct(
        private readonly CustomSettings $customSettings
    ) {
        parent::__construct($customSettings);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getCustomSettings(): CustomSettings
    {
        return $this->customSettings;
    }
}
