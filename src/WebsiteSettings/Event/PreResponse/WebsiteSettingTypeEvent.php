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

namespace Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\WebsiteSettings\Schema\WebsiteSettingType;

final class WebsiteSettingTypeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.website_settings.type';

    public function __construct(
        private readonly WebsiteSettingType $type
    ) {
        parent::__construct($this->type);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getWebsiteSettingType(): WebsiteSettingType
    {
        return $this->type;
    }
}
