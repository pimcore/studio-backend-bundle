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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataIndex\Grid;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DocumentSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearch;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DocumentQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\QueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder as DataObjectFolder;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final class GridSearchTest extends Unit
{
    private const int FOLDER_ID = 9;

    private const string FOLDER_PATH = '/workspace-parent';

    public function testDataObjectGridSearchesWithViewPermissionAndChecksListOnFolder(): void
    {
        $user = new User();
        $folder = $this->createParentElement(DataObjectFolder::class, $user);
        $filter = new FilterParameter();

        $dataObjectSearchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, [
            'searchDataObjects' => Expected::once(
                function (QueryInterface $query, PermissionTypes $permissionType) {
                    $this->assertSame(PermissionTypes::VIEW, $permissionType);

                    return new DataObjectSearchResult([], 1, 10, 0);
                }
            ),
            'getDataObjectById' => Expected::never(),
        ]);

        $gridSearch = $this->createGridSearch(
            dataObjectSearchService: $dataObjectSearchService,
            query: $this->makeEmpty(DataObjectQueryInterface::class),
            folder: $folder,
        );

        $gridSearch->searchElementsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            new GridParameter(self::FOLDER_ID, [], $filter),
            $user
        );

        $this->assertSame(self::FOLDER_PATH, $filter->getPath());
    }

    public function testAssetGridSearchesWithViewPermission(): void
    {
        $user = new User();
        $folder = $this->createParentElement(AssetFolder::class, $user);

        $assetSearchService = $this->makeEmpty(AssetSearchServiceInterface::class, [
            'searchAssets' => Expected::once(
                function (AssetQueryInterface $query, PermissionTypes $permissionType) {
                    $this->assertSame(PermissionTypes::VIEW, $permissionType);

                    return new AssetSearchResult([], 1, 10, 0);
                }
            ),
            'getAssetById' => Expected::never(),
        ]);

        $this->createGridSearch(
            assetSearchService: $assetSearchService,
            query: $this->makeEmpty(AssetQueryInterface::class),
            folder: $folder,
        )->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    public function testDocumentGridSearchesWithViewPermission(): void
    {
        $user = new User();
        $folder = $this->createParentElement(Page::class, $user);

        $documentSearchService = $this->makeEmpty(DocumentSearchServiceInterface::class, [
            'searchDocuments' => Expected::once(
                function (DocumentQueryInterface $query, PermissionTypes $permissionType) {
                    $this->assertSame(PermissionTypes::VIEW, $permissionType);

                    return new DocumentSearchResult([], 1, 10, 0);
                }
            ),
            'getDocumentById' => Expected::never(),
        ]);

        $this->createGridSearch(
            documentSearchService: $documentSearchService,
            query: $this->makeEmpty(DocumentQueryInterface::class),
            folder: $folder,
        )->searchElementsForUser(
            ElementTypes::TYPE_DOCUMENT,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    /**
     * Data objects can have children, so an object that is not a folder is accepted as grid folder.
     */
    public function testDataObjectGridAcceptsAnObjectAsFolder(): void
    {
        $user = new User();
        $object = $this->createParentElement(Concrete::class, $user);
        $filter = new FilterParameter();

        $this->createGridSearch(
            dataObjectSearchService: $this->makeEmpty(DataObjectSearchServiceInterface::class, [
                'searchDataObjects' => Expected::once(new DataObjectSearchResult([], 1, 10, 0)),
            ]),
            query: $this->makeEmpty(DataObjectQueryInterface::class),
            folder: $object,
        )->searchElementsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            new GridParameter(self::FOLDER_ID, [], $filter),
            $user
        );

        $this->assertSame(self::FOLDER_PATH, $filter->getPath());
    }

    /**
     * The full path of a document can be relative to its site, the index stores the real path.
     */
    public function testDocumentGridFiltersByTheRealPath(): void
    {
        $user = new User();
        $page = $this->makeEmpty(Page::class, [
            'getFullPath' => '/site-relative-path',
            'getRealFullPath' => self::FOLDER_PATH,
            'isAllowed' => true,
        ]);
        $filter = new FilterParameter();

        $this->createGridSearch(
            documentSearchService: $this->makeEmpty(DocumentSearchServiceInterface::class, [
                'searchDocuments' => new DocumentSearchResult([], 1, 10, 0),
            ]),
            query: $this->makeEmpty(DocumentQueryInterface::class),
            folder: $page,
        )->searchElementsForUser(
            ElementTypes::TYPE_DOCUMENT,
            new GridParameter(self::FOLDER_ID, [], $filter),
            $user
        );

        $this->assertSame(self::FOLDER_PATH, $filter->getPath());
    }

    public function testElementIdSearchUsesViewPermission(): void
    {
        $user = new User();
        $folder = $this->createParentElement(DataObjectFolder::class, $user);

        $dataObjectSearchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, [
            'fetchDataObjectIds' => Expected::once(
                function (QueryInterface $query, PermissionTypes $permissionType) {
                    $this->assertSame(PermissionTypes::VIEW, $permissionType);

                    return [12];
                }
            ),
        ]);

        $ids = $this->createGridSearch(
            dataObjectSearchService: $dataObjectSearchService,
            query: $this->makeEmpty(DataObjectQueryInterface::class),
            folder: $folder,
        )->searchElementIdsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );

        $this->assertSame([12], $ids);
    }

    public function testAssetIdSearchUsesViewPermission(): void
    {
        $user = new User();
        $folder = $this->createParentElement(AssetFolder::class, $user);

        $assetSearchService = $this->makeEmpty(AssetSearchServiceInterface::class, [
            'fetchAssetIds' => Expected::once(
                function (AssetQueryInterface $query, PermissionTypes $permissionType) {
                    $this->assertSame(PermissionTypes::VIEW, $permissionType);

                    return [12];
                }
            ),
        ]);

        $ids = $this->createGridSearch(
            assetSearchService: $assetSearchService,
            query: $this->makeEmpty(AssetQueryInterface::class),
            folder: $folder,
        )->searchElementIdsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );

        $this->assertSame([12], $ids);
    }

    /**
     * A folder the user may not list gets the same response as a missing folder, so ids cannot be probed.
     */
    public function testFolderWithoutListPermissionIsReportedLikeAMissingFolder(): void
    {
        $user = new User();
        $gridParameter = new GridParameter(self::FOLDER_ID, [], new FilterParameter());
        $query = $this->makeEmpty(DataObjectQueryInterface::class);
        $searchService = $this->makeEmpty(DataObjectSearchServiceInterface::class, [
            'searchDataObjects' => Expected::never(),
        ]);

        $unlistable = $this->captureNotFound(fn () => $this->createGridSearch(
            dataObjectSearchService: $searchService,
            query: $query,
            folder: $this->makeEmpty(DataObjectFolder::class, ['isAllowed' => false]),
        )->searchElementsForUser(ElementTypes::TYPE_DATA_OBJECT, $gridParameter, $user));

        $missing = $this->captureNotFound(fn () => $this->createGridSearch(
            dataObjectSearchService: $searchService,
            query: $query,
            folder: null,
        )->searchElementsForUser(ElementTypes::TYPE_DATA_OBJECT, $gridParameter, $user));

        $this->assertSame($missing->getMessage(), $unlistable->getMessage());
    }

    public function testAssetGridRequiresAnAssetFolder(): void
    {
        $user = new User();
        $image = $this->createParentElement(Image::class, $user);

        $gridSearch = $this->createGridSearch(
            assetSearchService: $this->makeEmpty(AssetSearchServiceInterface::class, [
                'searchAssets' => Expected::never(),
            ]),
            query: $this->makeEmpty(AssetQueryInterface::class),
            folder: $image,
        );

        $this->expectException(NotFoundException::class);
        $gridSearch->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    /**
     * @param class-string<ElementInterface> $class
     */
    private function createParentElement(string $class, User $user): ElementInterface
    {
        return $this->makeEmpty($class, [
            'getRealFullPath' => self::FOLDER_PATH,
            'isAllowed' => Expected::once(
                function (string $permission, User $permissionUser) use ($user) {
                    $this->assertSame(ElementPermissions::LIST_PERMISSION, $permission);
                    $this->assertSame($user, $permissionUser);

                    return true;
                }
            ),
        ]);
    }

    private function captureNotFound(callable $search): NotFoundException
    {
        try {
            $search();
        } catch (NotFoundException $exception) {
            return $exception;
        }

        $this->fail('Expected a NotFoundException.');
    }

    private function createGridSearch(
        QueryInterface $query,
        ?ElementInterface $folder,
        ?AssetSearchServiceInterface $assetSearchService = null,
        ?DataObjectSearchServiceInterface $dataObjectSearchService = null,
        ?DocumentSearchServiceInterface $documentSearchService = null,
    ): GridSearch {
        $filterService = $this->makeEmpty(SearchIndexFilterInterface::class, [
            'applyFilters' => $query,
        ]);

        return new GridSearch(
            $assetSearchService ?? $this->makeEmpty(AssetSearchServiceInterface::class),
            $dataObjectSearchService ?? $this->makeEmpty(DataObjectSearchServiceInterface::class),
            $documentSearchService ?? $this->makeEmpty(DocumentSearchServiceInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class, ['create' => $filterService]),
            $this->makeEmpty(QueryFactoryInterface::class, ['create' => $query]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => function (string $type, int $id) use ($folder) {
                    $this->assertSame($folder ? $this->getCoreType($folder) : 'object', $type);
                    $this->assertSame(self::FOLDER_ID, $id);

                    return $folder;
                },
            ]),
        );
    }

    private function getCoreType(ElementInterface $element): string
    {
        return match (true) {
            $element instanceof Asset => 'asset',
            $element instanceof Document => 'document',
            default => 'object',
        };
    }
}
