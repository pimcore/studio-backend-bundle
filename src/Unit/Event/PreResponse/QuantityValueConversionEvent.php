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
