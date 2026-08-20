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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\ObjectDependenciesRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\Dependency;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesService;
use Pimcore\Model\DataObject\Concrete;

/**
 * @internal
 */
final class ObjectDependenciesServiceTest extends Unit
{
    public function testDeniedObjectsAreFilteredOutButStillCountedInTotal(): void
    {
        $allowedObject = $this->makeEmpty(Concrete::class, [
            'isAllowed' => true,
        ]);
        $deniedObject = $this->makeEmpty(Concrete::class, [
            'isAllowed' => false,
        ]);

        $objectDependenciesRepository = $this->makeEmpty(ObjectDependenciesRepositoryInterface::class, [
            'getObjectsReferencingUser' => [
                'items' => [$allowedObject, $deniedObject],
                'totalItems' => 2,
            ],
        ]);
        $dependencyHydrator = $this->makeEmpty(DependencyHydratorInterface::class, [
            'hydrate' => $this->makeEmpty(Dependency::class),
        ]);

        $objectDependenciesService = new ObjectDependenciesService($objectDependenciesRepository, $dependencyHydrator);

        $collection = $objectDependenciesService->getPaginatedDependenciesForUser(1, new CollectionParameters(1, 10));

        $this->assertCount(1, $collection->getItems());
        $this->assertSame(2, $collection->getTotalItems());
    }
}
