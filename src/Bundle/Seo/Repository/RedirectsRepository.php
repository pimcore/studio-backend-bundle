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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Repository;

use Pimcore\Bundle\SeoBundle\Model\Redirect;
use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\ListingFilterInterface;

/**
 * @internal
 */
final readonly class RedirectsRepository implements RedirectsRepositoryInterface
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

    /**
     * {@inheritDoc}
     */
    public function getById(int $id): Redirect
    {
        $redirect = Redirect::getById($id);
        if (!$redirect instanceof Redirect) {
            throw new NotFoundException(type: 'redirect', id: $id);
        }

        return $redirect;
    }
}
