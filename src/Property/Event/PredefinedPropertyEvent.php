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

namespace Pimcore\Bundle\StudioBackendBundle\Property\Event;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Property\Schema\PredefinedProperty;

final class PredefinedPropertyEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.predefined_property';

    public function __construct(
        private readonly PredefinedProperty $predefinedProperty
    ) {
        parent::__construct($predefinedProperty);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getPredefinedProperty(): PredefinedProperty
    {
        return $this->predefinedProperty;
    }
}
