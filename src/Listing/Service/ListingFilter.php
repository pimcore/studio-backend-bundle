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

namespace Pimcore\Bundle\StudioBackendBundle\Listing\Service;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterLoaderInterface;

final readonly class ListingFilter implements ListingFilterInterface
{
    public function __construct(
        private FilterLoaderInterface $filterLoader
    ) {
    }


    public function applyFilters(
        FilterParameter $parameters,
        mixed $listing
    ): mixed {
        $filters = $this->filterLoader->loadFilters($listing);

        foreach ($filters->getFilters() as $filter) {
            $listing = $filter->apply($parameters, $listing);
        }

        return $listing;
    }

    public function getType(): string
    {
        return ListingFilterInterface::SERVICE_TYPE;
    }
}
