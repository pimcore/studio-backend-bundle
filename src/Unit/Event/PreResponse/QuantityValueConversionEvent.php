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

namespace Pimcore\Bundle\StudioBackendBundle\Unit\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Unit\Schema\ConvertedQuantityValues;

final class QuantityValueConversionEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.quantity_value.unit.conversion_collection';

    public function __construct(
        private readonly ConvertedQuantityValues $collection
    ) {
        parent::__construct($this->collection);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getCollection(): ConvertedQuantityValues
    {
        return $this->collection;
    }
}
