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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientCredentials;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ServiceUserClientStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Role\Repository\RoleRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Repository\McpAccessTokenRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Event\ServiceUserEvent;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Hydrator\ServiceUserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateFromUserParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\CreateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\MappedParameter\UpdateParameter;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUser as ServiceUserSchema;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpTokens;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserCredentialsService;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserSchemaBuilder;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\ServiceUserService;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UpdateServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\WorkspaceCloneServiceInterface;
use Pimcore\Model\User;
use Pimcore\Model\User\Role;
use Pimcore\Model\User\Workspace\Asset as AssetWorkspace;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_fill_keys;
use function in_array;

/**
 * @internal
 */
final class ServiceUserServiceTest extends Unit
{
    private const string SECRET = 'plain-secret';

    /** @var list<User> */
    private array $saved = [];

    /** @var list<User> */
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

    public function testCreateWithTheGrantOffRegistersTheClientButReturnsNoSecret(): void
    {
        $credentials = $this->service(clientCredentialsEnabled: false)->createServiceUser(
            new CreateParameter(name: 'svc-erp-sync'),
        );

        $this->assertCount(1, $this->registered, 'the OAuth client is still registered');
        $this->assertSame('svc_client', $credentials->getClientId());
        $this->assertNull($credentials->getClientSecret());
    }

    public function testCreateFromUserWithTheGrantOffReturnsNoSecret(): void
    {
        $human = new User();
        $human->setId(3);
        $human->setName('jane');

        $credentials = $this->service(
            userRepository: $this->userRepository($human),
            clientCredentialsEnabled: false,
        )->createServiceUserFromUser(3, new CreateFromUserParameter(name: 'svc-jane-sync'));

        $this->assertCount(1, $this->registered, 'the OAuth client is still registered');
        $this->assertSame('svc_client', $credentials->getClientId());
        $this->assertNull($credentials->getClientSecret());
    }

    public function testRotateWithTheGrantOffRotatesTheSecretButReturnsNoSecret(): void
    {
        $clientService = $this->makeEmpty(ServiceUserClientServiceInterface::class, [
            'rotateSecret' => Expected::once(new ClientCredentials('svc_client', 'rotated')),
        ]);
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            clientService: $clientService,
            clientCredentialsEnabled: false,
        );

        $credentials = $service->rotateSecret(42);

        $this->assertSame(42, $credentials->getServiceUserId());
        $this->assertSame('svc_client', $credentials->getClientId());
        $this->assertNull($credentials->getClientSecret());
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
        $source = User::newServiceUser();
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

    public function testRotateWithTheGrantOnReturnsTheSecret(): void
    {
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            clientService: $this->makeEmpty(ServiceUserClientServiceInterface::class, [
                'rotateSecret' => new ClientCredentials('svc_client', 'rotated'),
            ]),
            clientCredentialsEnabled: true,
        );

        $this->assertSame('rotated', $service->rotateSecret(42)->getClientSecret());
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

    public function testGetServiceUserCarriesTheTokenSummaryOfTheUser(): void
    {
        $queries = [];
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            tokenRepository: $this->makeEmpty(McpAccessTokenRepositoryInterface::class, [
                'summarizeByUserIds' => static function (array $userIds) use (&$queries): array {
                    $queries[] = $userIds;

                    return [42 => ['active' => 2, 'latestExpiresAt' => 2000000000]];
                },
            ]),
        );

        $tokens = $service->getServiceUser(42)->getMcpTokens();

        $this->assertSame(2, $tokens->getActive());
        $this->assertSame(2000000000, $tokens->getLatestExpiresAt());
        $this->assertSame([[42]], $queries);
    }

    public function testUserWithoutValidTokensGetsAnEmptySummary(): void
    {
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            tokenRepository: $this->makeEmpty(McpAccessTokenRepositoryInterface::class, [
                'summarizeByUserIds' => [],
            ]),
        );

        $tokens = $service->getServiceUser(42)->getMcpTokens();

        $this->assertSame(0, $tokens->getActive());
        $this->assertNull($tokens->getLatestExpiresAt());
    }

    public function testCollectionIssuesOneGroupedTokenQueryForAllUsers(): void
    {
        $first = $this->serviceUser();
        $second = $this->serviceUser();
        $second->setId(43);
        $third = $this->serviceUser();
        $third->setId(44);

        $queries = [];
        $service = $this->service(
            repository: $this->makeEmpty(ServiceUserRepositoryInterface::class, [
                'listServiceUsers' => [$first, $second, $third],
            ]),
            tokenRepository: $this->makeEmpty(McpAccessTokenRepositoryInterface::class, [
                'summarizeByUserIds' => static function (array $userIds) use (&$queries): array {
                    $queries[] = $userIds;

                    return [43 => ['active' => 1, 'latestExpiresAt' => 1900000000]];
                },
            ]),
        );

        $items = $service->listServiceUsers()->getItems();

        $this->assertSame([[42, 43, 44]], $queries);
        $this->assertSame(0, $items[0]->getMcpTokens()->getActive());
        $this->assertSame(1, $items[1]->getMcpTokens()->getActive());
        $this->assertSame(1900000000, $items[1]->getMcpTokens()->getLatestExpiresAt());
        $this->assertSame(0, $items[2]->getMcpTokens()->getActive());
    }

    public function testCollectionIssuesOneGroupedClientQueryForAllUsers(): void
    {
        $first = $this->serviceUser();
        $second = $this->serviceUser();
        $second->setId(43);

        $queries = [];
        $service = $this->service(
            repository: $this->makeEmpty(ServiceUserRepositoryInterface::class, [
                'listServiceUsers' => [$first, $second],
            ]),
            clientStore: $this->makeEmpty(ServiceUserClientStoreInterface::class, [
                'findByServiceUserId' => Expected::never(),
                'findClientIdsByServiceUserIds' => static function (array $serviceUserIds) use (&$queries): array {
                    $queries[] = $serviceUserIds;

                    return [43 => 'svc_client_43'];
                },
            ]),
        );

        $items = $service->listServiceUsers()->getItems();

        $this->assertSame([[42, 43]], $queries);
        $this->assertNull($items[0]->getClientId());
        $this->assertSame('svc_client_43', $items[1]->getClientId());
    }

    public function testASingleServiceUserLooksUpItsOwnClient(): void
    {
        $serviceUser = $this->serviceUser();
        $service = $this->service(
            repository: $this->repository($serviceUser),
            clientStore: $this->makeEmpty(ServiceUserClientStoreInterface::class, [
                'findByServiceUserId' => Expected::once($this->client()),
                'findClientIdsByServiceUserIds' => Expected::never(),
            ]),
        );

        $this->assertSame('svc_client', $service->getServiceUser(42)->getClientId());
    }

    public function testCreateAppliesResponsibleUsersAndRoles(): void
    {
        $service = $this->service(userRepository: $this->userRepositoryWith([3 => $this->human(3)]));

        $service->createServiceUser(new CreateParameter(
            name: 'svc-erp-sync',
            responsibleUsers: [3],
            responsibleRoles: [5],
        ));

        $this->assertSame([3], $this->saved[0]->getResponsibleUsers());
        $this->assertSame([5], $this->saved[0]->getResponsibleRoles());
    }

    public function testCreateWithAnUnknownResponsibleUserIsNotFoundAndRemovesTheServiceUser(): void
    {
        $service = $this->service(userRepository: $this->userRepositoryWith([]));

        try {
            $service->createServiceUser(new CreateParameter(name: 'svc-erp-sync', responsibleUsers: [99]));
            $this->fail('NotFoundException expected');
        } catch (NotFoundException) {
            $this->assertCount(1, $this->deleted);
        }
    }

    public function testUpdateAppliesResponsibleUsersAndRoles(): void
    {
        $existing = $this->serviceUser();
        $service = $this->service(
            repository: $this->repository($existing),
            userRepository: $this->userRepositoryWith([3 => $this->human(3), 4 => $this->human(4)]),
        );

        $schema = $service->updateServiceUser(42, new UpdateParameter(
            responsibleUsers: [3, 4],
            responsibleRoles: [5, 6],
        ));

        $this->assertSame([3, 4], $existing->getResponsibleUsers());
        $this->assertSame([5, 6], $existing->getResponsibleRoles());
        $this->assertSame(42, $schema->getId());
    }

    public function testUpdateWithEmptyListsClearsTheResponsibles(): void
    {
        $existing = $this->serviceUser();
        $existing->setResponsibleUsers([3]);
        $existing->setResponsibleRoles([5]);
        $service = $this->service(repository: $this->repository($existing));

        $service->updateServiceUser(42, new UpdateParameter(responsibleUsers: [], responsibleRoles: []));

        $this->assertSame([], $existing->getResponsibleUsers());
        $this->assertSame([], $existing->getResponsibleRoles());
        $this->assertSame($existing, $this->saved[0]);
    }

    public function testUpdateWithoutTheResponsibleKeysKeepsTheStoredResponsibles(): void
    {
        $existing = $this->serviceUser();
        $existing->setResponsibleUsers([3]);
        $existing->setResponsibleRoles([5]);
        $service = $this->service(repository: $this->repository($existing));

        $service->updateServiceUser(42, new UpdateParameter(permissions: ['assets']));

        $this->assertSame([3], $existing->getResponsibleUsers());
        $this->assertSame([5], $existing->getResponsibleRoles());
        $this->assertSame($existing, $this->saved[0]);
    }

    public function testUpdateWithAnUnknownResponsibleUserIsNotFound(): void
    {
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            userRepository: $this->userRepositoryWith([]),
        );

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageMatches('/User/');

        $service->updateServiceUser(42, new UpdateParameter(responsibleUsers: [99]));
    }

    public function testUpdateWithAnUnknownResponsibleRoleIsNotFound(): void
    {
        $existing = $this->serviceUser();
        $service = $this->service(repository: $this->repository($existing));

        try {
            $service->updateServiceUser(42, new UpdateParameter(responsibleRoles: [77]));
            $this->fail('NotFoundException expected');
        } catch (NotFoundException $exception) {
            $this->assertStringContainsString('Role', $exception->getMessage());
            $this->assertSame([], $this->saved);
        }
    }

    public function testUpdateRefusesAServiceUserAsResponsible(): void
    {
        $other = $this->serviceUser();
        $other->setId(7);
        $service = $this->service(
            repository: $this->repository($this->serviceUser()),
            userRepository: $this->userRepositoryWith([7 => $other]),
        );

        try {
            $service->updateServiceUser(42, new UpdateParameter(responsibleUsers: [7]));
            $this->fail('UnprocessableContentException expected');
        } catch (UnprocessableContentException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame('A service user cannot be responsible for a service user', $exception->getMessage());
            $this->assertSame([], $this->saved);
        }
    }

    private function service(
        ?ServiceUserRepositoryInterface $repository = null,
        ?UserRepositoryInterface $userRepository = null,
        ?ServiceUserClientServiceInterface $clientService = null,
        ?SecurityServiceInterface $currentUser = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?McpAccessTokenRepositoryInterface $tokenRepository = null,
        ?bool $clientCredentialsEnabled = null,
        ?ServiceUserClientStoreInterface $clientStore = null,
        ?RoleRepositoryInterface $roleRepository = null,
    ): ServiceUserService {
        return new ServiceUserService(
            $repository ?? $this->repository(),
            $userRepository ?? $this->makeEmpty(UserRepositoryInterface::class),
            $this->passThroughUpdateService(),
            $this->makeEmpty(WorkspaceCloneServiceInterface::class, [
                'cloneAssetWorkspace' => static fn (AssetWorkspace $workspace): AssetWorkspace => clone $workspace,
            ]),
            new ServiceUserCredentialsService(
                $clientService ?? $this->makeEmpty(ServiceUserClientServiceInterface::class, [
                    'register' => function (User $serviceUser, string $name, array $scopes): ClientCredentials {
                        $this->registered[] = [$serviceUser->getId(), $name, $scopes];

                        return new ClientCredentials('svc_client', self::SECRET);
                    },
                ]),
                $this->makeEmpty(ScopeRegistryInterface::class, ['all' => ['mcp:read', 'mcp:write']]),
                $clientCredentialsEnabled ?? true,
            ),
            new ServiceUserSchemaBuilder(
                $clientStore ?? $this->makeEmpty(ServiceUserClientStoreInterface::class, [
                    'findByServiceUserId' => $this->client(),
                    'findClientIdsByServiceUserIds' => static fn (array $ids): array => array_fill_keys(
                        $ids,
                        'svc_client'
                    ),
                ]),
                $this->makeEmpty(ServiceUserHydratorInterface::class, [
                    'hydrate' => static fn (
                        User $serviceUser,
                        ?string $clientId,
                        ServiceUserMcpTokens $mcpTokens
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
                        clientCredentialsEnabled: false,
                        mcpTokens: $mcpTokens,
                        responsibleUsers: [],
                        responsibleRoles: [],
                    ),
                ]),
                $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
                $tokenRepository ?? $this->makeEmpty(McpAccessTokenRepositoryInterface::class),
            ),
            $currentUser ?? $this->currentUser(isAdmin: false),
            $roleRepository ?? $this->roleRepository([5, 6]),
        );
    }

    private function client(): DynamicClient
    {
        return new DynamicClient('svc_client', 'svc', [], ['client_credentials'], [], true, 'hash');
    }

    private function repository(?User $existing = null): ServiceUserRepositoryInterface
    {
        return $this->makeEmpty(ServiceUserRepositoryInterface::class, [
            'getServiceUser' => static fn (int $id): User => $existing
                ?? throw new NotFoundException('Service user', $id),
            'saveServiceUser' => function (User $serviceUser): void {
                if ($serviceUser->getId() === null) {
                    $serviceUser->setId(42);
                }
                $this->saved[] = $serviceUser;
            },
            'deleteServiceUser' => function (User $serviceUser): void {
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

    /**
     * @param int[] $knownIds
     */
    private function roleRepository(array $knownIds): RoleRepositoryInterface
    {
        return $this->makeEmpty(RoleRepositoryInterface::class, [
            'getRoleById' => static function (int $id) use ($knownIds): Role {
                if (!in_array($id, $knownIds, true)) {
                    throw new NotFoundException('Role', $id);
                }

                return new Role();
            },
        ]);
    }

    /**
     * @param array<int, User> $users
     */
    private function userRepositoryWith(array $users): UserRepositoryInterface
    {
        return $this->makeEmpty(UserRepositoryInterface::class, [
            'getUserById' => static fn (int $id): User => $users[$id] ?? throw new NotFoundException('User', $id),
        ]);
    }

    private function human(int $id): User
    {
        $user = new User();
        $user->setId($id);

        return $user;
    }

    private function currentUser(bool $isAdmin): SecurityServiceInterface
    {
        $user = new User();
        $user->setId(1);
        $user->setAdmin($isAdmin);

        return $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]);
    }

    private function serviceUser(): User
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setId(42);
        $serviceUser->setName('svc-erp-sync');
        $serviceUser->setActive(true);

        return $serviceUser;
    }
}
