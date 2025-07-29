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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service;

use Exception;
use Pimcore\Bundle\SeoBundle\Model\Redirect\Listing;
use Pimcore\Bundle\SeoBundle\Redirect\RedirectHandler;
use Pimcore\Bundle\StaticResolverBundle\Models\Site\SiteResolverInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @internal
 */
final readonly class FilterService implements FilterServiceInterface
{
    public function __construct(
        private RedirectHandler $redirectHandler,
        private SiteResolverInterface $siteResolver,
    ) {
    }

    public function searchByRequest(Listing $listing, string $filterValue): Listing
    {
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
}
