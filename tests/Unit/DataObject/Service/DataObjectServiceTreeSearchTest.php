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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\DataObjectServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Provider\DataObjectQueryProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Query\DataObjectQueryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\DataObjectParameters;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\SearchIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\FactoryInterface;
use Pimcore\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class DataObjectServiceTreeSearchTest extends Unit
{
    /**
     * Filters such as the tree PQL filter apply the permissions of the query's user, so the user has to be
     * set before the filters run.
     */
    public function testUserIsSetBeforeFiltersAreApplied(): void
    {
        $events = [];
        $user = new User();
        $query = $this->makeEmpty(DataObjectQueryInterface::class, [
            'setUser' => function ($queryUser) use (&$events, $user, &$query) {
                $this->assertSame($user, $queryUser);
                $events[] = 'setUser';

                return $query;
            },
        ]);

        $service = new DataObjectService(
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(DataServiceInterface::class),
            $this->makeEmpty(DataObjectQueryProviderInterface::class, ['createDataObjectQuery' => $query]),
            $this->makeEmpty(DataObjectSearchServiceInterface::class),
            $this->makeEmpty(DataObjectServiceResolverInterface::class),
            $this->makeEmpty(FactoryInterface::class),
            $this->makeEmpty(FilterServiceProviderInterface::class, [
                'create' => $this->makeEmpty(SearchIndexFilterInterface::class, [
                    'applyFilters' => function () use (&$events, $query) {
                        $events[] = 'applyFilters';

                        return $query;
                    },
                ]),
            ]),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
            // The parent lookup after filtering fails on purpose, only the order up to that point matters.
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => null]),
            $this->makeEmpty(ElementSaveServiceInterface::class),
        );

        try {
            $service->getDataObjects(new DataObjectParameters(parentId: 1));
            $this->fail('The parent lookup is expected to fail.');
        } catch (NotFoundException) {
            $this->assertSame(['setUser', 'applyFilters'], $events);
        }
    }
}
