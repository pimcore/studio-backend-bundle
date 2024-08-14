<?php

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