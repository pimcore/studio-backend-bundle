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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\GenericDataIndexBundle\Enum\Permission\PermissionTypes;
use Pimcore\Bundle\StaticResolverBundle\Models\Asset\AssetServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetService;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\AssetQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\AssetQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AssetServiceTreeSearchTest extends Unit
{
    /**
     * Listing children of a node needs list permission only, so the tree can navigate to the user's workspaces.
     */
    public function testChildrenListingUsesListPermission(): void
    {
        $this->createService(PermissionTypes::LIST)->getAssets(new ElementParameters(parentId: 1));
    }

    /**
     * A content filter (PQL, id search term) must only match elements the user may view.
     *
     * @dataProvider contentFilterProvider
     */
    public function testContentFilterUsesViewPermission(?string $idSearchTerm, ?string $pqlQuery): void
    {
        $this->createService(PermissionTypes::VIEW)->getAssets(
            new ElementParameters(parentId: 1, idSearchTerm: $idSearchTerm, pqlQuery: $pqlQuery)
        );
    }

    public static function contentFilterProvider(): array
    {
        return [
            'id search term' => ['12', null],
            'pql query' => [null, 'filename like "a%"'],
        ];
    }

    private function createService(PermissionTypes $expectedPermissionType): AssetService
    {
        $query = $this->makeEmpty(AssetQueryInterface::class);

        return new AssetService(
            $this->makeEmpty(AssetQueryProviderInterface::class, ['createAssetQuery' => $query]),
            $this->makeEmpty(AssetSearchServiceInterface::class, [
                'searchAssets' => Expected::once(
                    function (AssetQueryInterface $assetQuery, PermissionTypes $permissionType) use ($expectedPermissionType) {
                        $this->assertSame($expectedPermissionType, $permissionType);

                        return new AssetSearchResult([], 1, 10, 0);
                    }
                ),
            ]),
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class, [
                'create' => $this->makeEmpty(SearchIndexFilterInterface::class, ['applyFilters' => $query]),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => new User()]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class),
        );
    }
}
