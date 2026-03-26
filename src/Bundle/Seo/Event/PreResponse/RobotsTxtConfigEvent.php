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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtConfig;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class RobotsTxtConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.bundle_seo.robots_txt_config';

    public function __construct(
        private readonly RobotsTxtConfig $robotsTxtConfig
    ) {
        parent::__construct($robotsTxtConfig);
    }

    public function getRobotsTxtConfig(): RobotsTxtConfig
    {
        return $this->robotsTxtConfig;
    }
}
