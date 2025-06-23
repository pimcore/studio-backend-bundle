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

namespace Pimcore\Bundle\StudioBackendBundle\RecycleBin\Repository;

use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;
use Pimcore\Model\Element\Recyclebin\Item\Listing;

/**
 * @internal
 */
final readonly class RecycleBinRepository implements RecycleBinRepositoryInterface
{
    public function __construct(
        private ListingFilterInterface $listingFilter
    ) {
    }

    public function getListing(FilterParameter $parameters): Listing
    {
        $listing = new Listing();
        $this->listingFilter->applyFilters($parameters, $listing);

        return $listing;
    }
}