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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\ServiceUserClientException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientService;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryOAuthClientStore;
use Pimcore\Model\User;
use function hash;
use function strlen;

/**
 * @internal
 */
final class ServiceUserClientServiceTest extends Unit
{
    private InMemoryOAuthClientStore $store;

    private ServiceUserClientService $service;

    public function _before(): void
    {
        $this->store = new InMemoryOAuthClientStore();
        $this->service = new ServiceUserClientService(
            $this->store,
            new ScopeRegistry(new ConfigProtectedResourceRegistry([
                ['uri' => 'https://pimcore.example.com/pimcore-mcp', 'scopes_supported' => ['mcp:read', 'mcp:write']],
            ])),
        );
    }

    public function testRegistrationStoresAConfidentialClientCredentialsClient(): void
    {
        $issued = $this->service->register($this->serviceUser(77), 'Pipeline', ['mcp:read']);

        $stored = $this->store->findByServiceUserId(77);
        $this->assertNotNull($stored);
        $this->assertSame($issued->clientId, $stored->identifier);
        $this->assertSame(77, $stored->serviceUserId);
        $this->assertTrue($stored->confidential);
        $this->assertSame(['client_credentials'], $stored->grantTypes);
        $this->assertSame([], $stored->redirectUris);
        $this->assertSame(['mcp:read'], $stored->scopes);
        $this->assertNull($stored->metadataHash);
    }

    /**
     * The secret is shown once: what is stored is its hash, never the secret itself.
     */
    public function testOnlyTheHashOfTheSecretIsStored(): void
    {
        $issued = $this->service->register($this->serviceUser(77), 'Pipeline', []);

        $stored = $this->store->find($issued->clientId);
        $this->assertNotNull($stored);
        $this->assertSame(hash('sha256', $issued->clientSecret), $stored->secretHash);
        $this->assertNotSame($issued->clientSecret, $stored->secretHash);
        $this->assertGreaterThanOrEqual(64, strlen($issued->clientSecret));
    }

    public function testAServiceUserGetsAtMostOneClient(): void
    {
        $user = $this->serviceUser(77);
        $this->service->register($user, 'Pipeline', []);

        $this->expectException(ServiceUserClientException::class);
        $this->service->register($user, 'Second', []);
    }

    public function testAnUnsavedServiceUserCannotBeBound(): void
    {
        $this->expectException(ServiceUserClientException::class);
        $this->service->register(User::newServiceUser(), 'Pipeline', []);
    }

    public function testAnUnknownScopeIsRefused(): void
    {
        $this->expectException(ServiceUserClientException::class);
        $this->service->register($this->serviceUser(77), 'Pipeline', ['admin:all']);
    }

    public function testRotationReplacesTheStoredHash(): void
    {
        $user = $this->serviceUser(77);
        $issued = $this->service->register($user, 'Pipeline', []);

        $rotated = $this->service->rotateSecret($user);

        $this->assertSame($issued->clientId, $rotated->clientId);
        $this->assertNotSame($issued->clientSecret, $rotated->clientSecret);
        $this->assertSame(hash('sha256', $rotated->clientSecret), $this->store->find($issued->clientId)?->secretHash);
    }

    public function testRotationWithoutABoundClientIsRefused(): void
    {
        $this->expectException(ServiceUserClientException::class);
        $this->service->rotateSecret($this->serviceUser(77));
    }

    private function serviceUser(int $id): User
    {
        $user = User::newServiceUser();
        $user->setId($id);
        $user->setActive(true);

        return $user;
    }
}
