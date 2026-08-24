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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\ObjectDependenciesRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\Dependency;
use Pimcore\Bundle\StudioBackendBundle\User\Service\ObjectDependenciesService;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\UserInterface;

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
            'hydrate' => new Dependency(1, '/path/to/object', 'Car'),
        ]);
        $userRepository = $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
        ]);

        $objectDependenciesService = new ObjectDependenciesService(
            $objectDependenciesRepository,
            $dependencyHydrator,
            $userRepository,
            $securityService
        );

        $collection = $objectDependenciesService->getPaginatedDependenciesForUser(1, new CollectionParameters(1, 10));

        $this->assertCount(1, $collection->getItems());
        $this->assertSame(2, $collection->getTotalItems());
    }

    public function testPreviewSetsHasHiddenAndTotalItems(): void
    {
        $deniedObject = $this->makeEmpty(Concrete::class, [
            'isAllowed' => false,
        ]);

        $objectDependenciesRepository = $this->makeEmpty(ObjectDependenciesRepositoryInterface::class, [
            'getObjectsReferencingUser' => [
                'items' => [$deniedObject],
                'totalItems' => 5000,
            ],
        ]);
        $dependencyHydrator = $this->makeEmpty(DependencyHydratorInterface::class);
        $userRepository = $this->makeEmpty(UserRepositoryInterface::class);
        $securityService = $this->makeEmpty(SecurityServiceInterface::class);

        $objectDependenciesService = new ObjectDependenciesService(
            $objectDependenciesRepository,
            $dependencyHydrator,
            $userRepository,
            $securityService
        );

        $preview = $objectDependenciesService->getPreviewForUser(1, 20);

        $this->assertTrue($preview->isHasHidden());
        $this->assertCount(0, $preview->getDependencies());
        $this->assertSame(5000, $preview->getTotalItems());
    }
}
