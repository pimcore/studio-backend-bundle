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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Factory\QueryFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset\Folder as AssetFolder;
use Pimcore\Model\Asset\Image;
use Pimcore\Model\DataObject\Folder as DataObjectFolder;
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
        $folder = $this->makeEmpty(DataObjectFolder::class, ['getRealFullPath' => self::FOLDER_PATH]);
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
            securityService: $this->createSecurityService($user, $folder),
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
        $folder = $this->makeEmpty(AssetFolder::class, ['getRealFullPath' => self::FOLDER_PATH]);

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
            securityService: $this->createSecurityService($user, $folder),
        )->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    public function testDocumentGridSearchesWithViewPermission(): void
    {
        $user = new User();
        $folder = $this->makeEmpty(Page::class, ['getRealFullPath' => self::FOLDER_PATH]);

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
            securityService: $this->createSecurityService($user, $folder),
        )->searchElementsForUser(
            ElementTypes::TYPE_DOCUMENT,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    public function testElementIdSearchUsesViewPermission(): void
    {
        $user = new User();
        $folder = $this->makeEmpty(DataObjectFolder::class, ['getRealFullPath' => self::FOLDER_PATH]);

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
            securityService: $this->createSecurityService($user, $folder),
        )->searchElementIdsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );

        $this->assertSame([12], $ids);
    }

    public function testFolderWithoutListPermissionIsRejected(): void
    {
        $user = new User();
        $folder = $this->makeEmpty(DataObjectFolder::class);

        $gridSearch = $this->createGridSearch(
            dataObjectSearchService: $this->makeEmpty(DataObjectSearchServiceInterface::class, [
                'searchDataObjects' => Expected::never(),
            ]),
            query: $this->makeEmpty(DataObjectQueryInterface::class),
            folder: $folder,
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'hasElementPermission' => static function () {
                    throw new ForbiddenException();
                },
            ]),
        );

        $this->expectException(ForbiddenException::class);
        $gridSearch->searchElementsForUser(
            ElementTypes::TYPE_DATA_OBJECT,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    public function testAssetGridRequiresAnAssetFolder(): void
    {
        $user = new User();
        $image = $this->makeEmpty(Image::class);

        $gridSearch = $this->createGridSearch(
            assetSearchService: $this->makeEmpty(AssetSearchServiceInterface::class, [
                'searchAssets' => Expected::never(),
            ]),
            query: $this->makeEmpty(AssetQueryInterface::class),
            folder: $image,
            securityService: $this->createSecurityService($user, $image),
        );

        $this->expectException(NotFoundException::class);
        $gridSearch->searchElementsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(self::FOLDER_ID, [], new FilterParameter()),
            $user
        );
    }

    private function createSecurityService(User $user, ElementInterface $folder): SecurityServiceInterface
    {
        return $this->makeEmpty(SecurityServiceInterface::class, [
            'hasElementPermission' => Expected::once(
                function (ElementInterface $element, User $permissionUser, string $permission) use ($user, $folder) {
                    $this->assertSame($folder, $element);
                    $this->assertSame($user, $permissionUser);
                    $this->assertSame(ElementPermissions::LIST_PERMISSION, $permission);
                }
            ),
        ]);
    }

    private function createGridSearch(
        QueryInterface $query,
        ElementInterface $folder,
        SecurityServiceInterface $securityService,
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
            $securityService,
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => function (string $type, int $id) use ($folder) {
                    $this->assertSame(self::FOLDER_ID, $id);

                    return $folder;
                },
            ]),
        );
    }
}
