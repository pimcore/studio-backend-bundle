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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\ElementSearch;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\SearchResult\ElementSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\SearchInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\Element\ElementSearchServiceInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Service\Search\SearchService\SearchProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SearchRepository;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;
use ReflectionClass;

/**
 * @internal
 */
final class SearchRepositoryTest extends Unit
{
    public function testSearchElementsOnlyReturnsElementsTheUserMayView(): void
    {
        $result = (new ReflectionClass(ElementSearchResult::class))->newInstanceWithoutConstructor();

        $repository = new SearchRepository(
            $this->makeEmpty(ElementSearchServiceInterface::class, [
                'search' => Expected::once(
                    function (SearchInterface $search, PermissionTypes $permissionType) use ($result) {
                        $this->assertSame(PermissionTypes::VIEW, $permissionType);

                        return $result;
                    }
                ),
            ]),
            $this->makeEmpty(SearchProviderInterface::class, ['createElementSearch' => new ElementSearch()]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()])
        );

        $this->assertSame($result, $repository->searchElements(new SimpleSearchParameter(searchTerm: 'car')));
    }
}
