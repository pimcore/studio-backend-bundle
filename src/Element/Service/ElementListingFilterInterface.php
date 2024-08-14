<?php

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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Listing\CallableFilterListingInterface;

interface ElementListingFilterInterface extends FilterServiceInterface
{
    public const SERVICE_TYPE = 'element_listing_filter';

    public function applyFilters(
        FilterParameter $parameters,
        AbstractListing|CallableFilterListingInterface $listing
    ): AbstractListing|CallableFilterListingInterface;
}
