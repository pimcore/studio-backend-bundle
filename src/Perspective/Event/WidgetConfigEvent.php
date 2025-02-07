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
