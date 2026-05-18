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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\ElementUsageItem;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ElementUsageItemEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.element.usage.item';

    public function __construct(
        private readonly ElementUsageItem $usageItem
    ) {
        parent::__construct($this->usageItem);
    }

    public function getUsageItem(): ElementUsageItem
    {
        return $this->usageItem;
    }
}
