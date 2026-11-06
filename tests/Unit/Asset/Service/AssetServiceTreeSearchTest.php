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

use Codeception\Test\Unit;
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
     * Filters such as the tree PQL filter apply the permissions of the query's user, so the user has to be
     * set before the filters run.
     */
    public function testUserIsSetBeforeFiltersAreApplied(): void
    {
        $events = [];
        $user = new User();
        $query = $this->makeEmpty(AssetQueryInterface::class, [
            'setUser' => function ($queryUser) use (&$events, $user, &$query) {
                $this->assertSame($user, $queryUser);
                $events[] = 'setUser';

                return $query;
            },
        ]);

        $service = new AssetService(
            $this->makeEmpty(AssetQueryProviderInterface::class, ['createAssetQuery' => $query]),
            $this->makeEmpty(AssetSearchServiceInterface::class, [
                'searchAssets' => new AssetSearchResult([], 1, 10, 0),
            ]),
            $this->makeEmpty(AssetServiceResolverInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class, [
                'create' => $this->makeEmpty(SearchIndexFilterInterface::class, [
                    'applyFilters' => function () use (&$events, $query) {
                        $events[] = 'applyFilters';

                        return $query;
                    },
                ]),
            ]),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(WorkflowDetailsServiceInterface::class),
        );

        $service->getAssets(new ElementParameters(parentId: 1));

        $this->assertSame(['setUser', 'applyFilters'], $events);
    }
}
