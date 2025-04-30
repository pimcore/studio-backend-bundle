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

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\LocationData;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ElementLocateEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.element_locate';

    public function __construct(private readonly LocationData $locationData)
    {
        parent::__construct($this->locationData);
    }

    public function getElementLocationData(): LocationData
    {
        return $this->locationData;
    }
}
