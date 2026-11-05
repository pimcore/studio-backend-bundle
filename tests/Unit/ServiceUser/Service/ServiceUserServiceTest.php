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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientCredentials;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Event\ServiceUserEvent;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator\ServiceUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateFromUserParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserService;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\ServiceUser;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class ServiceUserServiceTest extends Unit
{
    private const string SECRET = 'plain-secret';

    /** @var list<ServiceUser> */
    private array $saved = [];

    /** @var list<ServiceUser> */
    private array $deleted = [];

    /** @var list<array{int, string, list<string>}> */
    private array $registered = [];

    public function testCreateStoresAServiceUserAndReturnsItsClientCredentialsOnce(): void
    {
        $service = $this->service();

        $credentials = $service->createServiceUser(new CreateParameter(
            name: 'svc-erp-sync',
            email: 'erp-team@example.com',
            permissions: ['assets'],
            roles: [5],
            classes: ['CAR'],
            docTypes: ['3'],
        ));

        $serviceUser = $this->saved[0];
        $this->assertSame('svc-erp-sync', $serviceUser->getName());
        $this->assertSame('erp-team@example.com', $serviceUser->getEmail());
        $this->assertTrue($serviceUser->isActive());
        $this->assertFalse($serviceUser->isAdmin());
        $this->assertNull($serviceUser->getPassword());
        $this->assertSame(['assets'], $serviceUser->getPermissions());
        $this->assertSame([5], $serviceUser->getRoles());
        $this->assertSame(['CAR'], $serviceUser->getClasses());
        $this->assertSame(['3'], $serviceUser->getDocTypes());

        $this->assertSame([[42, 'svc-erp-sync', ['mcp:read', 'mcp:write']]], $this->registered);
        $this->assertSame(42, $credentials->getServiceUserId());
        $this->assertSame('svc_client', $credentials->getClientId());
        $this->assertSame(self::SECRET, $credentials->getClientSecret());
    }

    public function testCreateRegistersOnlyTheRequestedScopes(): void
    {
        $this->service()->createServiceUser(new CreateParameter(name: 'svc-erp-sync', scopes: ['mcp:read']));

        $this->assertSame([[42, 'svc-erp-sync', ['mcp:read']]], $this->registered);
    }

    public function testCreateRollsBackTheServiceUserWhenTheClientIsRefused(): void
    {
        $service = $this->service(clientService: $this->makeEmpty(ServiceUserClientServiceInterface::class, [
            'register' => static fn () => throw new ServiceUserClientException('Unknown scope "admin".'),
        ]));

        try {
            $service->createServiceUser(new CreateParameter(name: 'svc-erp-sync', scopes: ['admin']));
            $this->fail('An unknown scope must be refused.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('Unknown scope "admin".', $exception->getMessage());
        }

        $this->assertCount(1, $this->deleted);
        $this->assertSame('svc-erp-sync', $this->deleted[0]->getName());
    }

    public function testCreateWithATakenNameIsAConflict(): void
    {
        $service = $this->service(repository: $this->makeEmpty(ServiceUserRepositoryInterface::class, [
            'saveServiceUser' => static fn () => throw new ElementExistsException('The name is taken.'),
        ]));

        $this->expectException(ElementExistsException::class);
        $service->createServiceUser(new CreateParameter(name: 'jane'));
    }

    public function testCreateFromUserClonesPermissionsRolesAndWorkspacesButLeavesTheHumanUntouched(): void
    {
        $human = new User();
        $human->setId(3);
        $human->setName('jane');
        $human->setPermissions(['assets', 'objects']);
        $human->setRoles([5, 6]);
        $human->setClasses(['CAR']);
        $human->setDocTypes(['3']);
        $human->setWebsiteTranslationLanguagesView(['de']);
        $human->setWebsiteTranslationLanguagesEdit(['en']);
        $workspace = new AssetWorkspace();
        $workspace->setCpath('/products');
        $workspace->setUserId(3);
        $human->setWorkspacesAsset([$workspace]);

        $service = $this->service(userRepository: $this->userRepository($human));
        $credentials = $service->createServiceUserFromUser(3, new CreateFromUserParameter(name: 'svc-jane-sync'));

        $serviceUser = $this->saved[0];
        $this->assertSame('svc-jane-sync', $serviceUser->getName());
        $this->assertSame(['assets', 'objects'], $serviceUser->getPermissions());
        $this->assertSame([5, 6], $serviceUser->getRoles());
        $this->assertSame(['CAR'], $serviceUser->getClasses());
        $this->assertSame(['3'], $serviceUser->getDocTypes());
        $this->assertSame(['de'], $serviceUser->getWebsiteTranslationLanguagesView());
        $this->assertSame(['en'], $serviceUser->getWebsiteTranslationLanguagesEdit());
        $this->assertCount(1, $serviceUser->getWorkspacesAsset());
        $this->assertNotSame($workspace, $serviceUser->getWorkspacesAsset()[0]);
        $this->assertSame(42, $serviceUser->getWorkspacesAsset()[0]->getUserId());
        $this->assertSame(3, $workspace->getUserId(), 'the human workspace is not reassigned');
        $this->assertSame('jane', $human->getName());
        $this->assertSame(42, $credentials->getServiceUserId());
    }

    public function testCreateFromAnAdminNeverMakesAnAdmin(): void
    {
        $admin = new User();
        $admin->setId(2);
        $admin->setName('admin');
        $admin->setAdmin(true);

        $service = $this->service(
            userRepository: $this->userRepository($admin),
            currentUser: $this->currentUser(isAdmin: true),
        );
        $service->createServiceUserFromUser(2, new CreateFromUserParameter(name: 'svc-admin-copy'));

        $this->assertFalse($this->saved[0]->isAdmin());
    }

    public function testOnlyAnAdminMayCreateFromAnAdmin(): void
    {
        $admin = new User();
        $admin->setId(2);
        $admin->setAdmin(true);

        $service = $this->service(userRepository: $this->userRepository($admin));

        $this->expectException(ForbiddenException::class);
        $service->createServiceUserFromUser(2, new CreateFromUserParameter(name: 'svc-admin-copy'));
    }

    public function testCreateFromAServiceUserIsRefused(): void
    {
        $source = new ServiceUser();
        $source->setId(9);

        $service = $this->service(userRepository: $this->userRepository($source));

        $this->expectException(NotFoundException::class);
        $service->createServiceUserFromUser(9, new CreateFromUserParameter(name: 'svc-copy'));
    }

    public function testUpdateReplacesPermissionsRolesAndContact(): void
    {
        $serviceUser = $this->serviceUser();
        $serviceUser->setPermissions(['documents']);

        $service = $this->service(repository: $this->repository($serviceUser));
        $schema = $service->updateServiceUser(42, new UpdateParameter(
            email: 'new@example.com',
            permissions: ['assets'],
            roles: [6],
        ));

        $this->assertSame($serviceUser, $this->saved[0]);
        $this->assertSame('new@example.com', $serviceUser->getEmail());
        $this->assertSame(['assets'], $serviceUser->getPermissions());
        $this->assertSame([6], $serviceUser->getRoles());
        $this->assertFalse($serviceUser->isAdmin());
        $this->assertSame(42, $schema->getId());
    }

    public function testUpdateRefusesPasswordAdminTwoFactorAndRenameEvenPastValidation(): void
    {
        $serviceUser = $this->serviceUser();
        $service = $this->service(repository: $this->repository($serviceUser));

        try {
            $service->updateServiceUser(42, new UpdateParameter(permissions: ['assets'], admin: true));
            $this->fail('Admin rights must be refused.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('admin', $exception->getMessage());
        }

        $this->assertSame([], $this->saved);
        $this->assertFalse($serviceUser->isAdmin());
    }

    public function testDeactivateBlocksTheServiceUser(): void
    {
        $serviceUser = $this->serviceUser();

        $schema = $this->service(repository: $this->repository($serviceUser))->deactivateServiceUser(42);

        $this->assertFalse($serviceUser->isActive());
        $this->assertSame($serviceUser, $this->saved[0]);
        $this->assertSame(42, $schema->getId());
    }

    public function testDeleteDeletesTheServiceUser(): void
    {
        $serviceUser = $this->serviceUser();

        $this->service(repository: $this->repository($serviceUser))->deleteServiceUser(42);

        $this->assertSame([$serviceUser], $this->deleted);
    }

    public function testAnIdThatIsNoServiceUserIsNotFound(): void
    {
        $service = $this->service(repository: $this->makeEmpty(ServiceUserRepositoryInterface::class, [
            'getServiceUser' => static fn (int $id) => throw new NotFoundException('Service user', $id),
        ]));

        $this->expectException(NotFoundException::class);
        $service->deleteServiceUser(3);
    }

    public function testRotateReturnsTheNewSecretOnce(): void
    {
        $serviceUser = $this->serviceUser();
        $service = $this->service(
            repository: $this->repository($serviceUser),
            clientService: $this->makeEmpty(ServiceUserClientServiceInterface::class, [
                'rotateSecret' => Expected::once(new ClientCredentials('svc_client', 'rotated')),
            ]),
        );

        $credentials = $service->rotateSecret(42);

        $this->assertSame(42, $credentials->getServiceUserId());
        $this->assertSame('svc_client', $credentials->getClientId());
        $this->assertSame('rotated', $credentials->getClientSecret());
    }

    public function testRotateWithoutAClientIsNotFound(): void
    {
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            clientService: $this->makeEmpty(ServiceUserClientServiceInterface::class, [
                'rotateSecret' => static fn () => throw new ServiceUserClientException(
                    'This service user has no client.'
                ),
            ]),
        );

        $this->expectException(NotFoundException::class);
        $service->rotateSecret(42);
    }

    public function testGetHydratesWithTheClientIdAndDispatchesTheEvent(): void
    {
        $dispatched = [];
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            eventDispatcher: $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static function (object $event, ?string $name = null) use (&$dispatched): object {
                    $dispatched[] = $name;

                    return $event;
                },
            ]),
        );

        $schema = $service->getServiceUser(42);

        $this->assertSame('svc_client', $schema->getClientId());
        $this->assertSame([ServiceUserEvent::EVENT_NAME], $dispatched);
    }

    private function service(
        ?ServiceUserRepositoryInterface $repository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ServiceUserClientServiceInterface $clientService = null,
        ?SecurityServiceInterface $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
    ): ServiceUserService {
        return new ServiceUserService(
            $repository ?? $this->repository(),
            $userRepository ?? $this->makeEmpty(UserRepositoryInterface::class),
            $this->passThroughUpdateService(),
            $this->makeEmpty(WorkspaceCloneServiceInterface::class, [
                'cloneAssetWorkspace' => static fn (AssetWorkspace $workspace): AssetWorkspace => clone $workspace,
            ]),
            $clientService ?? $this->makeEmpty(ServiceUserClientServiceInterface::class, [
                'register' => function (ServiceUser $serviceUser, string $name, array $scopes): ClientCredentials {
                    $this->registered[] = [$serviceUser->getId(), $name, $scopes];

                    return new ClientCredentials('svc_client', self::SECRET);
                },
            ]),
            $this->makeEmpty(ServiceUserClientStoreInterface::class, [
                'findByServiceUserId' => new DynamicClient(
                    'svc_client',
                    'svc',
                    [],
                    ['client_credentials'],
                    [],
                    true,
                    'hash'
                ),
            ]),
            $this->makeEmpty(ServiceUserHydratorInterface::class, [
                'hydrate' => static fn (
                    ServiceUser $serviceUser,
                    ?string $clientId
                ): ServiceUserSchema => new ServiceUserSchema(
                    id: (int) $serviceUser->getId(),
                    name: (string) $serviceUser->getName(),
                    email: $serviceUser->getEmail(),
                    active: $serviceUser->isActive(),
                    permissions: [],
                    roles: [],
                    classes: [],
                    docTypes: [],
                    assetWorkspaces: [],
                    dataObjectWorkspaces: [],
                    documentWorkspaces: [],
                    clientId: $clientId,
                    lastLogin: null,
                ),
            ]),
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $currentUser ?? $this->currentUser(isAdmin: false),
            $this->makeEmpty(ScopeRegistryInterface::class, ['all' => ['mcp:read', 'mcp:write']]),
        );
    }

    private function repository(?ServiceUser $existing = null): ServiceUserRepositoryInterface
    {
        return $this->makeEmpty(ServiceUserRepositoryInterface::class, [
            'getServiceUser' => static fn (int $id): ServiceUser => $existing
                ?? throw new NotFoundException('Service user', $id),
            'saveServiceUser' => function (ServiceUser $serviceUser): void {
                if ($serviceUser->getId() === null) {
                    $serviceUser->setId(42);
                }
                $this->saved[] = $serviceUser;
            },
            'deleteServiceUser' => function (ServiceUser $serviceUser): void {
                $this->deleted[] = $serviceUser;
            },
        ]);
    }

    private function userRepository(User $user): UserRepositoryInterface
    {
        return $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => static fn (int $id): User => $user,
        ]);
    }

    private function passThroughUpdateService(): UpdateServiceInterface
    {
        return $this->makeEmpty(UpdateServiceInterface::class, [
            'updatePermissions' => static fn (array $permissions, $user) => $user->setPermissions($permissions),
            'updateRoles' => static fn (array $roles, $user) => $user->setRoles($roles),
            'updateClasses' => static fn (array $classes, $user) => $user->setClasses($classes),
            'updateAssetWorkspaces' => static fn (array $workspaces, $user) => $user,
            'updateDataObjectWorkspaces' => static fn (array $workspaces, $user) => $user,
            'updateDocumentWorkspaces' => static fn (array $workspaces, $user) => $user,
        ]);
    }

    private function currentUser(bool $isAdmin): SecurityServiceInterface
    {
        $user = new User();
        $user->setId(1);
        $user->setAdmin($isAdmin);

        return $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]);
    }

    private function serviceUser(): ServiceUser
    {
        $serviceUser = new ServiceUser();
        $serviceUser->setId(42);
        $serviceUser->setName('svc-erp-sync');
        $serviceUser->setActive(true);

        return $serviceUser;
    }
}
