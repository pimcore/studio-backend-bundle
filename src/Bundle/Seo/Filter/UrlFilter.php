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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Filter;

use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing as RedirectListing;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\FilterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;

/**
 * @internal
 */
final readonly class UrlFilter implements FilterInterface
{
    public function __construct(
        private FilterServiceInterface $filterService,
    ) {
    }

    public function apply(
        mixed $parameters,
        mixed $listing
    ): mixed {
        $column = $this->getColumn($parameters);
        if ($column === null) {
            return $listing;
        }

        return $this->filterService->searchByRequest($listing, $column->getFilterValue());
    }

    public function supports(mixed $listing): bool
    {
        return $listing instanceof RedirectListing;
    }

    private function getColumn(mixed $parameters): ?ColumnFilter
    {
        if (!$parameters instanceof FilterParameter) {
            return null;
        }

        $column = $parameters->getFirstColumnFilterByType('url');
        if ($column === null || !preg_match('@^https?://@', $column->getFilterValue())) {
            return null;
        }

        return $column;
    }
}
