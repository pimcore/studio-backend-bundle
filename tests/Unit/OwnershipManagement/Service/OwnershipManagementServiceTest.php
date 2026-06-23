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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OwnershipManagement\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\ConfigurationTypeHydrator;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\ConfigurationTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\DeleteParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\ReassignOwnerParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Schema\OwnershipConfiguration;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\JobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\OwnershipManagementService;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service\ProviderLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class OwnershipManagementServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetAvailableTypesSortsByPriorityDescending(): void
    {
        $loader = $this->makeEmpty(ProviderLoaderInterface::class, [
            'getProviders' => [
                'low' => $this->createProvider('low', 1),
                'high' => $this->createProvider('high', 10),
                'mid' => $this->createProvider('mid', 5),
            ],
        ]);

        $service = $this->createService(loader: $loader);

        $result = $service->getAvailableTypes();

        $this->assertSame(3, $result->getTotalItems());
        $this->assertSame(
            ['high', 'mid', 'low'],
            array_map(static fn ($item) => $item->getType(), $result->getItems())
        );
    }

    /**
     * @throws Exception
     */
    public function testThrowsForbiddenWhenCurrentUserIsNotAdmin(): void
    {
        $loader = $this->makeEmpty(ProviderLoaderInterface::class, [
            'getProviders' => Expected::never(),
            'resolve' => Expected::never(),
        ]);

        $service = $this->createService(loader: $loader, securityService: $this->adminSecurityService(false));

        $this->expectException(ForbiddenException::class);
        $service->delete('grid_configuration', new DeleteParameter(['1', '2']));
    }

    /**
     * @throws Exception
     */
    public function testGetAvailableTypesDispatchesEventForEachType(): void
    {
        $loader = $this->makeEmpty(ProviderLoaderInterface::class, [
            'getProviders' => [
                'a' => $this->createProvider('a', 1),
                'b' => $this->createProvider('b', 2),
            ],
        ]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::exactly(2, static fn (object $event): object => $event),
        ]);

        $service = $this->createService(loader: $loader, eventDispatcher: $eventDispatcher);

        $service->getAvailableTypes();
    }

    /**
     * @throws Exception
     */
    public function testListConfigurationsDispatchesEventForEachItem(): void
    {
        $collection = new Collection(2, [
            new OwnershipConfiguration('1', 'grid_configuration', 'First', 1),
            new OwnershipConfiguration('2', 'grid_configuration', 'Second', 2),
        ]);

        $provider = $this->makeEmpty(OwnershipProviderInterface::class, [
            'listConfigurations' => $collection,
        ]);

        $loader = $this->makeEmpty(ProviderLoaderInterface::class, ['resolve' => $provider]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::exactly(2, static fn (object $event): object => $event),
        ]);

        $service = $this->createService(loader: $loader, eventDispatcher: $eventDispatcher);

        $result = $service->listConfigurations('grid_configuration', new CollectionFilterParameter());

        $this->assertSame($collection, $result);
    }

    /**
     * @throws Exception
     */
    public function testReassignOwnerThrowsWhenNewOwnerDoesNotExist(): void
    {
        $loader = $this->makeEmpty(ProviderLoaderInterface::class, [
            'resolve' => Expected::never(),
        ]);

        $userService = $this->makeEmpty(UserServiceInterface::class, [
            'getUserNameById' => null,
        ]);

        $service = $this->createService(loader: $loader, userService: $userService);

        $this->expectException(InvalidArgumentException::class);
        $service->reassignOwner('grid_configuration', new ReassignOwnerParameter(['1'], 999));
    }

    /**
     * @throws Exception
     */
    public function testReassignOwnerProcessesSingleIdSynchronously(): void
    {
        $provider = $this->makeEmpty(OwnershipProviderInterface::class, [
            'reassignOwner' => Expected::once(),
        ]);

        $loader = $this->makeEmpty(ProviderLoaderInterface::class, ['resolve' => $provider]);
        $userService = $this->makeEmpty(UserServiceInterface::class, ['getUserNameById' => 'john_doe']);
        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createReassignOwnerJob' => Expected::never(),
        ]);

        $service = $this->createService(loader: $loader, userService: $userService, jobService: $jobService);

        $this->assertNull($service->reassignOwner('grid_configuration', new ReassignOwnerParameter(['1'], 7)));
    }

    /**
     * @throws Exception
     */
    public function testReassignOwnerCreatesJobForMultipleIds(): void
    {
        $provider = $this->makeEmpty(OwnershipProviderInterface::class, [
            'reassignOwner' => Expected::never(),
        ]);

        $loader = $this->makeEmpty(ProviderLoaderInterface::class, ['resolve' => $provider]);
        $userService = $this->makeEmpty(UserServiceInterface::class, ['getUserNameById' => 'john_doe']);
        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createReassignOwnerJob' => Expected::once(77),
        ]);

        $service = $this->createService(loader: $loader, userService: $userService, jobService: $jobService);

        $this->assertSame(77, $service->reassignOwner('grid_configuration', new ReassignOwnerParameter(['1', '2'], 7)));
    }

    /**
     * @throws Exception
     */
    public function testDeleteProcessesSingleIdSynchronously(): void
    {
        $provider = $this->makeEmpty(OwnershipProviderInterface::class, [
            'delete' => Expected::once(),
        ]);

        $loader = $this->makeEmpty(ProviderLoaderInterface::class, ['resolve' => $provider]);
        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createDeleteJob' => Expected::never(),
        ]);

        $service = $this->createService(loader: $loader, jobService: $jobService);

        $this->assertNull($service->delete('grid_configuration', new DeleteParameter(['1'])));
    }

    /**
     * @throws Exception
     */
    public function testDeleteCreatesJobForMultipleIds(): void
    {
        $provider = $this->makeEmpty(OwnershipProviderInterface::class, [
            'delete' => Expected::never(),
        ]);

        $loader = $this->makeEmpty(ProviderLoaderInterface::class, ['resolve' => $provider]);
        $jobService = $this->makeEmpty(JobServiceInterface::class, [
            'createDeleteJob' => Expected::once(88),
        ]);

        $service = $this->createService(loader: $loader, jobService: $jobService);

        $this->assertSame(88, $service->delete('grid_configuration', new DeleteParameter(['1', '2'])));
    }

    /**
     * @throws Exception
     */
    private function createProvider(string $type, int $sortPriority): OwnershipProviderInterface
    {
        return $this->makeEmpty(OwnershipProviderInterface::class, [
            'getType' => $type,
            'getLabel' => 'label_' . $type,
            'getIcon' => 'icon',
            'getSortPriority' => $sortPriority,
        ]);
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?ProviderLoaderInterface $loader = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?UserServiceInterface $userService = null,
        ?ConfigurationTypeHydratorInterface $configurationTypeHydrator = null,
        ?JobServiceInterface $jobService = null,
        ?SecurityServiceInterface $securityService = null,
    ): OwnershipManagementService {
        return new OwnershipManagementService(
            $loader ?? $this->makeEmpty(ProviderLoaderInterface::class),
            $configurationTypeHydrator ?? new ConfigurationTypeHydrator(),
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $userService ?? $this->makeEmpty(UserServiceInterface::class),
            $jobService ?? $this->makeEmpty(JobServiceInterface::class),
            $securityService ?? $this->adminSecurityService(),
        );
    }

    /**
     * @throws Exception
     */
    private function adminSecurityService(bool $isAdmin = true): SecurityServiceInterface
    {
        return $this->makeEmpty(SecurityServiceInterface::class, [
            'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => $isAdmin]),
        ]);
    }
}
