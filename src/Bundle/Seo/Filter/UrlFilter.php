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

use Exception;
use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing as RedirectListing;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Pimcore\Bundle\StaticResolverBundle\Models\Site\SiteResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Filter\FilterInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\Filter\ColumnFilter;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final readonly class UrlFilter implements FilterInterface
{
    public function __construct(
        private RedirectHandler $redirectHandler,
        private SiteResolverInterface $siteResolver,
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

        $filterValue = $column->getFilterValue();

        try {
            $dummyRequest = Request::create($filterValue);
            $site = $this->siteResolver->getByDomain($dummyRequest->getHost());
            $dummyResponse = $this->redirectHandler->checkForRedirect($dummyRequest, false, $site);
            $redirectId = -1;
            if ($dummyResponse) {
                $redirectId = $dummyResponse->headers->get(RedirectHandler::RESPONSE_HEADER_NAME_ID);
                if ($redirectId === null) {
                    $redirectId = -1; // Fallback if no ID is set
                }
            }

            $listing->addConditionParam(
                'id = :id',
                ['id' => $redirectId]
            );

        } catch (Exception) {
            $listing->setCondition('1 = 2');
        }

        return $listing;
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
