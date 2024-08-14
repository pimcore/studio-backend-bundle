<?php

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service;

use Pimcore\Bundle\StudioBackendBundle\Element\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\FilterParameter;
use Pimcore\Model\Listing\AbstractListing;
use Pimcore\Model\Listing\CallableFilterListingInterface;

final readonly class ElementListingFilter implements ElementListingFilterInterface
{
    public function __construct(private FilterLoaderInterface $filterLoader)
    {
    }
    public function applyFilters(
        FilterParameter $parameters,
        AbstractListing|CallableFilterListingInterface $listing
    ): AbstractListing|CallableFilterListingInterface
    {
        $filters = $this->filterLoader->loadFilters();

        foreach($filters->getFilters() as $filter) {
            $listing = $filter->apply($parameters, $listing);
        }

        return $listing;
    }

    public function getType(): string
    {
        return ElementListingFilterInterface::SERVICE_TYPE;
    }
}