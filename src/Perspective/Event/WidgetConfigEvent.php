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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;

final class WidgetConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.perspective.widget.config.get';

    public function __construct(
        private readonly WidgetConfig $widgetConfig
    ) {
        parent::__construct($widgetConfig);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getWidgetConfig(): WidgetConfig
    {
        return $this->widgetConfig;
    }
}
