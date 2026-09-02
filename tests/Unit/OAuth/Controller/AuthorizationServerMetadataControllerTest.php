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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Controller\AuthorizationServerMetadataController;
use Symfony\Component\HttpFoundation\Request;
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
        $metadata = $this->metadata(new AuthorizationServerMetadataController(self::ISSUER));

        // The client_credentials grant was removed; the metadata must not advertise it.
        $this->assertSame(['authorization_code', 'refresh_token'], $metadata['grant_types_supported']);
    }

    public function testAdvertisesTheCoreDiscoveryFields(): void
    {
        $metadata = $this->metadata(new AuthorizationServerMetadataController(self::ISSUER));

        $this->assertSame(self::ISSUER, $metadata['issuer']);
        $this->assertSame(self::ISSUER . '/pimcore-oauth/token', $metadata['token_endpoint']);
        $this->assertSame(['S256'], $metadata['code_challenge_methods_supported']);
    }

    public function testRegistrationEndpointOnlyAdvertisedWhenDcrEnabled(): void
    {
        $disabled = $this->metadata(new AuthorizationServerMetadataController(self::ISSUER));
        $this->assertArrayNotHasKey('registration_endpoint', $disabled);

        $enabled = $this->metadata(new AuthorizationServerMetadataController(self::ISSUER, false, true));
        $this->assertSame(self::ISSUER . '/pimcore-oauth/register', $enabled['registration_endpoint']);
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
