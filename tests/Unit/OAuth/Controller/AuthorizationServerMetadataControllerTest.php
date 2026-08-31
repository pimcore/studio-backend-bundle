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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ScopeRegistryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Controller\AuthorizationServerMetadataController;
use Symfony\Component\HttpFoundation\Request;
use function in_array;
use function is_array;
use function json_decode;

/**
 * @internal
 */
final class AuthorizationServerMetadataControllerTest extends Unit
{
    private const string ISSUER = 'https://issuer.test';

    public function testDoesNotAdvertiseTheRemovedClientCredentialsGrant(): void
    {
        $metadata = $this->metadata($this->controller());

        // The client_credentials grant was removed; the metadata must not advertise it.
        $this->assertSame(['authorization_code', 'refresh_token'], $metadata['grant_types_supported']);
    }

    public function testAdvertisesTheCoreDiscoveryFields(): void
    {
        $metadata = $this->metadata($this->controller());

        $this->assertSame(self::ISSUER, $metadata['issuer']);
        $this->assertSame(self::ISSUER . '/pimcore-oauth/token', $metadata['token_endpoint']);
        $this->assertSame(['S256'], $metadata['code_challenge_methods_supported']);
    }

    public function testAdvertisesTheScopesContributedByTheRegistry(): void
    {
        // The catalogue is extensible, so the advertised scopes are whatever the
        // registry holds -- not a list hard-coded in the controller.
        $metadata = $this->metadata($this->controller(scopes: ['mcp:read', 'datahub:read']));

        $this->assertSame(['mcp:read', 'datahub:read'], $metadata['scopes_supported']);
    }

    public function testRegistrationEndpointOnlyAdvertisedWhenDcrEnabled(): void
    {
        $disabled = $this->metadata($this->controller());
        $this->assertArrayNotHasKey('registration_endpoint', $disabled);

        $enabled = $this->metadata($this->controller(registrationEnabled: true));
        $this->assertSame(self::ISSUER . '/pimcore-oauth/register', $enabled['registration_endpoint']);
    }

    /**
     * @param list<string> $scopes
     */
    private function controller(
        array $scopes = ['mcp:read'],
        bool $registrationEnabled = false,
    ): AuthorizationServerMetadataController {
        return new AuthorizationServerMetadataController(
            self::ISSUER,
            $this->scopeRegistry($scopes),
            false,
            $registrationEnabled,
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function scopeRegistry(array $scopes): ScopeRegistryInterface
    {
        return new class($scopes) implements ScopeRegistryInterface {
            /**
             * @param list<string> $scopes
             */
            public function __construct(private readonly array $scopes)
            {
            }

            public function all(): array
            {
                return $this->scopes;
            }

            public function has(string $scope): bool
            {
                return in_array($scope, $this->scopes, true);
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function metadata(AuthorizationServerMetadataController $controller): array
    {
        $decoded = json_decode((string) $controller(new Request())->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }
}
