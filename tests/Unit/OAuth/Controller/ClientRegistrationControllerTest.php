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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Controller\ClientRegistrationController;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\ClientRegistrar;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\DynamicClientStoreInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use function json_decode;
use function json_encode;

/**
 * @internal
 */
final class ClientRegistrationControllerTest extends Unit
{
    /**
     * Registration writes a row from an open, unauthenticated endpoint, so the controller
     * has to refuse on its own rather than relying on OAuthEndpointGuardSubscriber being
     * the only thing in front of it. The extension passes
     * `dcr.enabled && oauth.enabled` here, so an install carrying a stale registration
     * flag while OAuth is off lands on this branch.
     */
    public function testDisabledAnswersNotFoundAndWritesNoRow(): void
    {
        $store = $this->store();

        $response = (new ClientRegistrationController($this->registrar($store), false))(
            $this->registrationRequest()
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(['error' => 'not_found'], $this->decode($response));
        $this->assertSame([], $store->saved, 'A refused registration must not persist a client.');
    }

    /**
     * The counterpart: with both flags on, the same request does register. Without this
     * the 404 above would also pass against a controller that never worked.
     */
    public function testEnabledRegistersTheClient(): void
    {
        $store = $this->store();

        $response = (new ClientRegistrationController($this->registrar($store), true))(
            $this->registrationRequest()
        );

        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertCount(1, $store->saved);
    }

    /**
     * The refusal must be indistinguishable from a route that was never declared, so a
     * probe cannot tell a disabled server from one that does not have the feature.
     */
    public function testDisabledResponseCarriesNoDetail(): void
    {
        $response = (new ClientRegistrationController($this->registrar($this->store()), false))(
            $this->registrationRequest()
        );

        $this->assertSame(['error' => 'not_found'], $this->decode($response));
    }

    private function registrar(DynamicClientStoreInterface $store): ClientRegistrar
    {
        $registry = new ConfigProtectedResourceRegistry([
            ['uri' => 'https://example.com/pimcore-mcp', 'scopes_supported' => ['mcp:read']],
        ]);

        return new ClientRegistrar($store, new ScopeRegistry($registry));
    }

    /**
     * @return DynamicClientStoreInterface&object{saved: array<string, DynamicClient>}
     */
    private function store(): DynamicClientStoreInterface
    {
        return new class implements DynamicClientStoreInterface {
            /** @var array<string, DynamicClient> */
            public array $saved = [];

            public function save(DynamicClient $client): void
            {
                $this->saved[$client->identifier] = $client;
            }

            public function find(string $identifier): ?DynamicClient
            {
                return $this->saved[$identifier] ?? null;
            }
        };
    }

    private function registrationRequest(): Request
    {
        return Request::create(
            '/pimcore-oauth/register',
            'POST',
            [],
            [],
            [],
            [],
            (string) json_encode([
                'client_name' => 'Probe',
                'redirect_uris' => ['https://app.example/cb'],
                'token_endpoint_auth_method' => 'none',
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getContent(), true);

        return $decoded;
    }
}
