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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server;

use DateInterval;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Grant\LoopbackAuthCodeGrant;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AccessTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AuthCodeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\RefreshTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use RuntimeException;
use function sprintf;

/**
 * Builds the isolated league authorization server from bundle configuration.
 * A standalone instance with its own repositories and keys — it does not touch
 * the application's global security services.
 *
 * @internal
 */
final class AuthorizationServerFactory
{
    public function __construct(
        private readonly ClientRepository $clientRepository,
        private readonly AccessTokenRepository $accessTokenRepository,
        private readonly ScopeRepository $scopeRepository,
        private readonly AuthCodeRepository $authCodeRepository,
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly ?string $privateKey,
        private readonly ?string $passphrase,
        private readonly ?string $encryptionKey,
        private readonly int $accessTokenTtl,
        private readonly int $authCodeTtl,
        private readonly int $refreshTokenTtl,
        private readonly bool $allowLocalhostLoopback,
    ) {
    }

    public function create(): AuthorizationServer
    {
        if ($this->privateKey === null || $this->encryptionKey === null) {
            throw new RuntimeException(
                'The embedded OAuth server needs a signing key and an encryption key '
                . '(pimcore_studio_backend.oauth.keys.private_key / encryption_key).'
            );
        }

        // CryptKey accepts either PEM contents or a key path; permission checking
        // is left to the deployment (key files should be restricted to the web user).
        $server = new AuthorizationServer(
            $this->clientRepository,
            $this->accessTokenRepository,
            $this->scopeRepository,
            new CryptKey($this->privateKey, $this->passphrase, false),
            $this->encryptionKey,
        );

        $accessTokenTtl = new DateInterval(sprintf('PT%dS', $this->accessTokenTtl));
        $refreshTokenTtl = new DateInterval(sprintf('PT%dS', $this->refreshTokenTtl));

        $server->enableGrantType(new ClientCredentialsGrant(), $accessTokenTtl);

        $authCodeGrant = new LoopbackAuthCodeGrant(
            $this->authCodeRepository,
            $this->refreshTokenRepository,
            new DateInterval(sprintf('PT%dS', $this->authCodeTtl)),
            $this->allowLocalhostLoopback,
        );
        $authCodeGrant->setRefreshTokenTTL($refreshTokenTtl);
        $server->enableGrantType($authCodeGrant, $accessTokenTtl);

        $refreshTokenGrant = new RefreshTokenGrant($this->refreshTokenRepository);
        $refreshTokenGrant->setRefreshTokenTTL($refreshTokenTtl);
        $server->enableGrantType($refreshTokenGrant, $accessTokenTtl);

        return $server;
    }
}
