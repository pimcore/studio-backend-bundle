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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Service;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceInterface;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Listing\CallableFilterListingInterface;

interface ListingFilterInterface extends FilterServiceInterface
{
    public const SERVICE_TYPE = 'element_listing_filter';

    public function applyFilters(
        FilterParameter $parameters,
        AbstractListing|CallableFilterListingInterface $listing
    ): AbstractListing|CallableFilterListingInterface;
}
