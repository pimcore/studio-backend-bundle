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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Search\Repository;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\ElementSearchException;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\ElementSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\ElementSearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Modifier\FullTextSearch\FullTextSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SearchRepository;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\FullTextSearchFields;
use Pimcore\Model\User;

/**
 * @internal
 */
final class SearchRepositoryTest extends Unit
{
    public function testSearchElementsRestrictsFullTextSearchToExplicitFields(): void
    {
        $search = new ElementSearch();
        $searchedElements = null;

        $elementSearchService = $this->makeEmpty(ElementSearchServiceInterface::class, [
            'search' => function (ElementSearchInterface $elementSearch) use (&$searchedElements) {
                $searchedElements = $elementSearch;

                throw new ElementSearchException('stop after capturing the search');
            },
        ]);
        $searchProvider = $this->makeEmpty(SearchProviderInterface::class, [
            'createElementSearch' => $search,
        ]);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => new User(),
        ]);

        $repository = new SearchRepository($elementSearchService, $searchProvider, $securityService);

        try {
            $repository->searchElements(new SimpleSearchParameter(searchTerm: 'pro'));
            $this->fail('Expected the search exception thrown by the stubbed search service');
        } catch (SearchException) {
        }

        $this->assertNotNull($searchedElements);
        $fullTextSearches = array_values(array_filter(
            $searchedElements->getModifiers(),
            static fn (object $modifier): bool => $modifier instanceof FullTextSearch
        ));

        $this->assertCount(1, $fullTextSearches);
        $this->assertSame('pro', $fullTextSearches[0]->getSearchTerm());
        $this->assertSame(FullTextSearchFields::FIELDS, $fullTextSearches[0]->getFields());
    }
}
