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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Repository;

use Pimcore\Bundle\GenericDataIndexBundle\Enum\Search\SortDirection;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\ElementSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\SearchResult\ElementSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\FullTextSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\Sort\Tree\OrderByFullPath;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class SearchRepository implements SearchRepositoryInterface
{
    public function __construct(
        private ElementSearchServiceInterface $elementSearchService,
        private SearchProviderInterface $searchProvider,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws SearchException|UserNotFoundException
     */
    public function searchElements(SimpleSearchParameter $parameter): ElementSearchResult
    {
        $search = $this->searchProvider->createElementSearch();
        /** @var User $user */
        $user = $this->securityService->getCurrentUser();
        $search->setUser($user);
        $search->setPageSize($parameter->getPageSize());
        $search->setPage($parameter->getPage());
        $search->addModifier(new OrderByFullPath(SortDirection::ASC));

        if ($parameter->getSearchTerm() !== null) {
            $search->addModifier(new FullTextSearch($parameter->getSearchTerm()));
        }

        try {
            return $this->elementSearchService->search($search);
        } catch (ElementSearchException $exception) {
            throw new SearchException('elements', $exception);
        }
    }
}
