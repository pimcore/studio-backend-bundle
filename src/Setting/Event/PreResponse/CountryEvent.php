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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\Country;

final class CountryEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.settings.available_country';

    public function __construct(
        private readonly Country $country
    ) {
        parent::__construct($country);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getCountry(): Country
    {
        return $this->country;
    }
}
