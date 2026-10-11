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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Search\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\SearchIndex\ElementType;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\DataObject\SearchResult\DataObjectSearchResultItem;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Element\SearchResult\ElementSearchResult;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Interfaces\ElementSearchResultItemInterface;
use Pimcore\Bundle\GenericDataIndexBundle\Model\Search\Paging\PaginationInfo;
use Pimcore\Bundle\GenericDataIndexBundle\Permission\DataObjectPermissions;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Hydrator\SimpleSearchHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SimpleSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Repository\SearchRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\SimpleSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SearchService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\User;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class SearchServiceViewPermissionTest extends Unit
{
    /**
     * Items the index reports without view permission are only returned if the core permission check allows them.
     */
    public function testQuickSearchDropsItemsWithoutViewPermission(): void
    {
        $viewableId = 12;
        $listOnlyId = 9;
        $wronglyDeniedId = 13;
        $user = new User();

        $items = [
            $this->createItem($viewableId, true),
            $this->createItem($listOnlyId, false),
            $this->createItem($wronglyDeniedId, false),
        ];

        $hydrated = [];
        $service = new SearchService(
            $this->makeEmpty(ElementServiceInterface::class, [
                'getElementById' => fn (string $type, int $id): Folder => $this->makeEmpty(Folder::class, [
                    'isAllowed' => fn (string $permission, User $permissionUser): bool => $id === $wronglyDeniedId
                        && $permission === 'view'
                        && $permissionUser === $user,
                ]),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => static fn (object $event) => $event]),
            $this->makeEmpty(SearchRepositoryInterface::class, [
                'searchElements' => new ElementSearchResult($items, new PaginationInfo(3, 1, 50, 1)),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
            $this->makeEmpty(ServiceProviderInterface::class),
            $this->makeEmpty(SimpleSearchHydratorInterface::class, [
                'hydrate' => function (ElementSearchResultItemInterface $item) use (&$hydrated): SimpleSearchResult {
                    $hydrated[] = $item->getId();

                    return (new ReflectionClass(SimpleSearchResult::class))->newInstanceWithoutConstructor();
                },
            ]),
        );

        $service->doSimpleSearch(new SimpleSearchParameter(searchTerm: 'car'));

        $this->assertSame([$viewableId, $wronglyDeniedId], $hydrated);
    }

    /**
     * Items with workflow permissions are checked against the core permissions even if the index grants view.
     */
    public function testQuickSearchChecksCoreViewForItemsWithWorkflowPermissions(): void
    {
        $user = new User();
        $plainId = 20;
        $deniedByPlaceId = 21;
        $items = [
            $this->createItem($plainId, true),
            $this->createItem($deniedByPlaceId, true, true),
        ];

        $checked = [];
        $hydrated = [];
        $service = new SearchService(
            $this->makeEmpty(ElementServiceInterface::class, [
                'getElementById' => function (string $type, int $id) use (&$checked): Folder {
                    $checked[] = $id;

                    return $this->makeEmpty(Folder::class, ['isAllowed' => false]);
                },
            ]),
            $this->makeEmpty(EventDispatcherInterface::class, ['dispatch' => static fn (object $event) => $event]),
            $this->makeEmpty(SearchRepositoryInterface::class, [
                'searchElements' => new ElementSearchResult($items, new PaginationInfo(2, 1, 50, 1)),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
            $this->makeEmpty(ServiceProviderInterface::class),
            $this->makeEmpty(SimpleSearchHydratorInterface::class, [
                'hydrate' => function (ElementSearchResultItemInterface $item) use (&$hydrated): SimpleSearchResult {
                    $hydrated[] = $item->getId();

                    return (new ReflectionClass(SimpleSearchResult::class))->newInstanceWithoutConstructor();
                },
            ]),
        );

        $result = $service->doSimpleSearch(new SimpleSearchParameter(searchTerm: 'car'));

        // Only the element with workflow permissions is loaded and checked; the plain one trusts the index.
        $this->assertSame([$deniedByPlaceId], $checked);
        $this->assertSame([$plainId], $hydrated);
        $this->assertSame(1, $result->getTotalItems());
    }

    private function createItem(int $id, bool $view, bool $hasWorkflow = false): ElementSearchResultItemInterface
    {
        $permissions = new DataObjectPermissions();
        $permissions->setView($view);

        return $this->makeEmpty(DataObjectSearchResultItem::class, [
            'isHasWorkflowWithPermissions' => $hasWorkflow,
            'getId' => $id,
            'getElementType' => ElementType::DATA_OBJECT,
            'getPermissions' => $permissions,
        ]);
    }
}
