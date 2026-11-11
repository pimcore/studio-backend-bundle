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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Server;

use const OPENSSL_KEYTYPE_RSA;
use Closure;
use Codeception\Test\Unit;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\UnencryptedToken;
use League\OAuth2\Server\Exception\OAuthServerException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use Pimcore\Bundle\StudioBackendBundle\Entity\OAuth\OAuthTokenRecord;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ClientMetadataResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\IdentityResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ClientMetadata;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\DynamicClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ConfigProtectedResourceRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Registry\ScopeRegistry;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactory;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AccessTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\AuthCodeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ClientRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\RefreshTokenRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Repository\ScopeRepository;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\ServiceUserClientService;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryOAuthClientStore;
use Pimcore\Bundle\StudioBackendBundle\Tests\Support\Fixture\InMemoryOAuthTokenRecordStore;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function hash;
use function json_decode;
use function openssl_pkey_export;
use function openssl_pkey_get_details;
use function openssl_pkey_new;

/**
 * Drives the server AuthorizationServerFactory builds through a client_credentials token
 * request, on the real client, token and scope repositories, because the rules for this
 * grant are spread over all of them: the factory decides whether the grant exists, the
 * client repository who may use it, the grant which identity and resource the token is
 * for, and the token entity what ends up in `sub`.
 *
 * @internal
 */
final class ClientCredentialsGrantTest extends Unit
{
    private const string ISSUER = 'https://pimcore.example.com';

    private const string RESOURCE = 'https://pimcore.example.com/pimcore-mcp';

    private const string OTHER_RESOURCE = 'https://pimcore.example.com/other';

    private const string BOUND_CLIENT = 'svc_bound';

    private const string SECRET = 'bound-client-secret';

    private const int SERVICE_USER_ID = 77;

    private const int TTL = 600;

    private const string ENCRYPTION_KEY = 'def000004242424242424242424242424242424242424242424242424242424242';

    private InMemoryOAuthTokenRecordStore $tokenStore;

    private InMemoryOAuthClientStore $clientStore;

    private string $privateKey;

    private string $publicKey;

    /**
     * @var array<int, UserInterface>
     */
    private array $users;

    public function _before(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
        /** @var array{key: string} $details */
        $details = openssl_pkey_get_details($key);
        $this->privateKey = (string) $privateKey;
        $this->publicKey = $details['key'];

        $this->tokenStore = new InMemoryOAuthTokenRecordStore();
        $this->clientStore = new InMemoryOAuthClientStore();
        $this->clientStore->save($this->boundClient(self::BOUND_CLIENT, self::SERVICE_USER_ID, self::SECRET));

        $serviceUser = User::newServiceUser();
        $serviceUser->setId(self::SERVICE_USER_ID);
        $serviceUser->setActive(true);
        $this->users = [self::SERVICE_USER_ID => $serviceUser];
    }

    public function testTheGrantIsRefusedWhileTheSwitchIsOff(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest(), enabled: false));

        $this->assertSame('unsupported_grant_type', $exception->getErrorType());
        $this->assertSame([], $this->tokenStore->identifiersOfType(OAuthTokenRecord::TYPE_ACCESS));
    }

    public function testABoundClientGetsATokenForItsServiceUser(): void
    {
        $response = $this->token($this->boundRequest());
        $claims = $this->claims($response['access_token'])->claims();

        $this->assertSame((string) self::SERVICE_USER_ID, $claims->get('sub'));
        $this->assertSame(self::BOUND_CLIENT, $claims->get('client_id'));
        $this->assertSame([self::RESOURCE], $claims->get('aud'));
        $this->assertSame(self::ISSUER, $claims->get('iss'));
        $this->assertIsString($claims->get('jti'));
        $this->assertSame('Bearer', $response['token_type']);
    }

    public function testTheTokenExpiresAfterTheConfiguredTtl(): void
    {
        $response = $this->token($this->boundRequest());
        $claims = $this->claims($response['access_token'])->claims();

        $this->assertSame(self::TTL, $response['expires_in']);
        $this->assertSame(
            self::TTL,
            $claims->get('exp')->getTimestamp() - $claims->get('iat')->getTimestamp(),
        );
    }

    public function testNoRefreshTokenIsIssued(): void
    {
        $response = $this->token($this->boundRequest());

        $this->assertArrayNotHasKey('refresh_token', $response);
        $this->assertSame([], $this->tokenStore->identifiersOfType(OAuthTokenRecord::TYPE_REFRESH));
    }

    public function testTheTokenRecordCarriesTheServiceUser(): void
    {
        $this->token($this->boundRequest());

        $this->assertSame([self::SERVICE_USER_ID], $this->tokenStore->userIdsOfType(OAuthTokenRecord::TYPE_ACCESS));
    }

    public function testWithoutARequestedScopeTheTokenCarriesTheRegisteredScopes(): void
    {
        $response = $this->token($this->boundRequest());

        $this->assertSame('mcp:read mcp:write', $this->claims($response['access_token'])->claims()->get('scope'));
    }

    public function testARequestedScopeWithinTheRegistrationIsGranted(): void
    {
        $response = $this->token($this->boundRequest(['scope' => 'mcp:read']));

        $this->assertSame('mcp:read', $this->claims($response['access_token'])->claims()->get('scope'));
    }

    public function testAScopeOutsideTheRegistrationIsRefused(): void
    {
        $this->clientStore->save(
            $this->boundClient(self::BOUND_CLIENT, self::SERVICE_USER_ID, self::SECRET, ['mcp:read'])
        );

        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest(['scope' => 'mcp:write'])));

        $this->assertSame('invalid_scope', $exception->getErrorType());
    }

    public function testTheTokenIsNarrowedToWhatTheResourceSupports(): void
    {
        $response = $this->token($this->boundRequest(['resource' => self::OTHER_RESOURCE]));
        $claims = $this->claims($response['access_token'])->claims();

        $this->assertSame([self::OTHER_RESOURCE], $claims->get('aud'));
        $this->assertSame('mcp:read', $claims->get('scope'));
    }

    public function testAMissingResourceIsRefused(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest(['resource' => null])));

        $this->assertSame('invalid_request', $exception->getErrorType());
    }

    public function testAnUnknownResourceIsRefused(): void
    {
        $exception = $this->refusal(
            fn (): array => $this->token($this->boundRequest(['resource' => 'https://elsewhere.example.com/mcp']))
        );

        $this->assertSame('invalid_request', $exception->getErrorType());
    }

    public function testAWrongSecretIsRefused(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest(['client_secret' => 'wrong'])));

        $this->assertSame('invalid_client', $exception->getErrorType());
    }

    public function testAMissingSecretIsRefused(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest(['client_secret' => null])));

        $this->assertSame('invalid_request', $exception->getErrorType());
    }

    public function testAPreRegisteredPublicClientIsRefused(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->request('studio-mcp', 'anything')));

        $this->assertContains($exception->getErrorType(), ['invalid_client', 'unauthorized_client']);
        $this->assertNoAccessToken();
    }

    public function testACimdClientIsRefused(): void
    {
        $exception = $this->refusal(
            fn (): array => $this->token($this->request('https://client.example.com/metadata.json', 'anything'))
        );

        $this->assertContains($exception->getErrorType(), ['invalid_client', 'unauthorized_client']);
        $this->assertNoAccessToken();
    }

    public function testAPublicDynamicClientIsRefused(): void
    {
        $this->clientStore->save(
            new DynamicClient('dcr_public', 'Public', ['https://app/cb'], ['authorization_code'], [], false, null)
        );

        $exception = $this->refusal(fn (): array => $this->token($this->request('dcr_public', 'anything')));

        $this->assertContains($exception->getErrorType(), ['invalid_client', 'unauthorized_client']);
        $this->assertNoAccessToken();
    }

    /**
     * Even a confidential client that somehow carries the grant in its registration is
     * refused while it is not bound to a service user: the token would have no subject.
     */
    public function testAnUnboundConfidentialClientIsRefused(): void
    {
        $this->clientStore->save(new DynamicClient(
            'dcr_confidential',
            'Confidential',
            [],
            ['client_credentials'],
            ['mcp:read'],
            true,
            hash('sha256', self::SECRET),
        ));

        $exception = $this->refusal(fn (): array => $this->token($this->request('dcr_confidential', self::SECRET)));

        $this->assertContains($exception->getErrorType(), ['invalid_client', 'unauthorized_client']);
        $this->assertNoAccessToken();
    }

    public function testABoundClientWithoutAStoredSecretIsRefused(): void
    {
        $this->clientStore->save(new DynamicClient(
            'svc_keyless',
            'Keyless',
            [],
            ['client_credentials'],
            ['mcp:read'],
            true,
            null,
            serviceUserId: self::SERVICE_USER_ID,
        ));

        $exception = $this->refusal(fn (): array => $this->token($this->request('svc_keyless', self::SECRET)));

        $this->assertSame('invalid_client', $exception->getErrorType());
    }

    public function testADeactivatedServiceUserGetsNoToken(): void
    {
        $this->users = [];

        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest()));

        $this->assertSame('invalid_client', $exception->getErrorType());
        $this->assertNoAccessToken();
    }

    /**
     * A bound client must never mint tokens for a human, whatever the record says.
     */
    public function testAClientBoundToAHumanUserGetsNoToken(): void
    {
        $human = new User();
        $human->setId(self::SERVICE_USER_ID);
        $this->users = [self::SERVICE_USER_ID => $human];

        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest()));

        $this->assertSame('invalid_client', $exception->getErrorType());
        $this->assertNoAccessToken();
    }

    public function testABoundClientCannotUseTheAuthorizationCodeGrant(): void
    {
        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest([
            'grant_type' => 'authorization_code',
            'code' => 'irrelevant',
            'redirect_uri' => 'http://127.0.0.1:8080/cb',
        ])));

        $this->assertSame('unauthorized_client', $exception->getErrorType());
    }

    public function testARotatedSecretInvalidatesTheOldOneAtOnce(): void
    {
        $serviceUser = $this->users[self::SERVICE_USER_ID];
        $this->assertTrue($serviceUser->isServiceUser());

        $service = new ServiceUserClientService($this->clientStore, $this->scopeRegistry());
        $rotated = $service->rotateSecret($serviceUser);

        $this->assertSame(self::BOUND_CLIENT, $rotated->clientId);
        $this->assertNotSame(self::SECRET, $rotated->clientSecret);

        $exception = $this->refusal(fn (): array => $this->token($this->boundRequest()));
        $this->assertSame('invalid_client', $exception->getErrorType());

        $response = $this->token($this->boundRequest(['client_secret' => $rotated->clientSecret]));
        $this->assertSame(
            (string) self::SERVICE_USER_ID,
            $this->claims($response['access_token'])->claims()->get('sub'),
        );
    }

    public function testARegisteredClientWorksWithTheSecretShownOnce(): void
    {
        $other = User::newServiceUser();
        $other->setId(78);
        $other->setActive(true);
        $this->users[78] = $other;

        $issued = (new ServiceUserClientService($this->clientStore, $this->scopeRegistry()))
            ->register($other, 'Pipeline', ['mcp:read']);

        $response = $this->token($this->request($issued->clientId, $issued->clientSecret));
        $claims = $this->claims($response['access_token'])->claims();

        $this->assertSame('78', $claims->get('sub'));
        $this->assertSame('mcp:read', $claims->get('scope'));
    }

    private function assertNoAccessToken(): void
    {
        $this->assertSame([], $this->tokenStore->identifiersOfType(OAuthTokenRecord::TYPE_ACCESS));
    }

    /**
     * @param callable(): array<string, mixed> $call
     */
    private function refusal(callable $call): OAuthServerException
    {
        try {
            $call();
        } catch (OAuthServerException $exception) {
            return $exception;
        }

        $this->fail('The token request was accepted.');
    }

    /**
     * @param array<string, string|null> $overrides null removes a parameter
     *
     * @return array<string, string>
     */
    private function boundRequest(array $overrides = []): array
    {
        return $this->request(self::BOUND_CLIENT, self::SECRET, $overrides);
    }

    /**
     * @param array<string, string|null> $overrides null removes a parameter
     *
     * @return array<string, string>
     */
    private function request(string $clientId, string $secret, array $overrides = []): array
    {
        $body = [
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $secret,
            'resource' => self::RESOURCE,
            ...$overrides,
        ];

        $filtered = [];
        foreach ($body as $name => $value) {
            if ($value !== null) {
                $filtered[$name] = $value;
            }
        }

        return $filtered;
    }

    /**
     * @param array<string, string> $body
     *
     * @return array<string, mixed>
     */
    private function token(array $body, bool $enabled = true): array
    {
        $request = (new ServerRequest('POST', self::ISSUER . '/pimcore-oauth/token'))->withParsedBody($body);
        $response = $this->factory($enabled)->create()->respondToAccessTokenRequest($request, new Response());

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode((string) $response->getBody(), true);

        return $decoded;
    }

    private function factory(bool $enabled): AuthorizationServerFactory
    {
        $resources = $this->resourceRegistry();

        return new AuthorizationServerFactory(
            new ClientRepository(
                ['studio-mcp' => ['name' => 'Studio MCP', 'redirect_uris' => ['http://127.0.0.1:8080/cb']]],
                $this->cimdResolver(),
                $this->clientStore,
            ),
            new AccessTokenRepository(self::ISSUER, $this->tokenStore),
            new ScopeRepository(new ScopeRegistry($resources)),
            new AuthCodeRepository($this->tokenStore),
            new RefreshTokenRepository($this->tokenStore),
            $this->privateKey,
            null,
            self::ENCRYPTION_KEY,
            3600,
            600,
            2592000,
            true,
            $resources,
            $this->tokenStore,
            $this->identityResolver(),
            $enabled,
            self::TTL,
        );
    }

    private function resourceRegistry(): ConfigProtectedResourceRegistry
    {
        return new ConfigProtectedResourceRegistry([
            ['uri' => self::RESOURCE, 'scopes_supported' => ['mcp:read', 'mcp:write']],
            ['uri' => self::OTHER_RESOURCE, 'scopes_supported' => ['mcp:read']],
        ]);
    }

    private function scopeRegistry(): ScopeRegistry
    {
        return new ScopeRegistry($this->resourceRegistry());
    }

    private function cimdResolver(): ClientMetadataResolverInterface
    {
        return new class() implements ClientMetadataResolverInterface {
            public function resolve(string $clientId): ?ClientMetadata
            {
                return new ClientMetadata($clientId, 'CIMD client', ['http://127.0.0.1:8080/cb']);
            }
        };
    }

    /**
     * Stands in for EmbeddedIdentityResolver: the users this test declares are the active
     * ones, everything else resolves to null the way a deleted or deactivated user does.
     */
    private function identityResolver(): IdentityResolverInterface
    {
        $users = fn (string $subject): ?UserInterface => $this->users[(int) $subject] ?? null;

        return new class($users) implements IdentityResolverInterface {
            /**
             * @param Closure(string): ?UserInterface $users
             */
            public function __construct(private readonly Closure $users)
            {
            }

            public function resolve(string $subject): ?UserInterface
            {
                return ($this->users)($subject);
            }
        };
    }

    /**
     * @param list<string> $scopes
     */
    private function boundClient(
        string $clientId,
        int $serviceUserId,
        string $secret,
        array $scopes = ['mcp:read', 'mcp:write']
    ): DynamicClient {
        return new DynamicClient(
            $clientId,
            'Bound',
            [],
            ['client_credentials'],
            $scopes,
            true,
            hash('sha256', $secret),
            serviceUserId: $serviceUserId,
        );
    }

    private function claims(mixed $jwt): UnencryptedToken
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->publicKey),
            InMemory::plainText($this->publicKey),
        );
        $token = $configuration->parser()->parse((string) $jwt);
        $this->assertInstanceOf(UnencryptedToken::class, $token);

        return $token;
    }
}
