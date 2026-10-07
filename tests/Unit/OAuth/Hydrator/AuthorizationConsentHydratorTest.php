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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\Hydrator;

use Codeception\Test\Unit;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Hydrator\AuthorizationConsentHydrator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class AuthorizationConsentHydratorTest extends Unit
{
    private const string CLIENT_ID = 'my-dev-pimcore';

    private const string REDIRECT_URI = 'https://oauth.tools/callback/code';

    private AuthorizationConsentHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new AuthorizationConsentHydrator();
    }

    public function testHydrateCarriesClientScopesAndUser(): void
    {
        $consent = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), ['test:read', 'test:write']),
            $this->user(22, 'admin'),
        );

        $this->assertSame('a1b2c3', $consent->getAuthorizationId());
        $this->assertSame(self::CLIENT_ID, $consent->getClient()->getIdentifier());
        $this->assertSame('My Dev Pimcore', $consent->getClient()->getName());
        $this->assertSame('oauth.tools', $consent->getClient()->getRedirectHost());
        $this->assertSame(['test:read', 'test:write'], $consent->getScopes());
        $this->assertNotNull($consent->getUser());
        $this->assertSame(22, $consent->getUser()->getId());
        $this->assertSame('admin', $consent->getUser()->getUsername());
    }

    /**
     * The screen may only name what the token will carry, so the scope list has to be the
     * one on the request rather than anything the client asked for earlier.
     */
    public function testScopesAreTakenFromTheRequest(): void
    {
        $consent = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), ['test:read']),
            $this->user(22, 'admin'),
        );

        $this->assertSame(['test:read'], $consent->getScopes());
    }

    public function testOnlyPreRegisteredClientsAreVerified(): void
    {
        $preRegistered = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), []),
            $this->user(22, 'admin'),
        );
        $dynamic = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest(
                new ClientEntity(self::CLIENT_ID, 'Self registered', self::REDIRECT_URI),
                [],
            ),
            $this->user(22, 'admin'),
        );

        $this->assertTrue($preRegistered->getClient()->isVerified());
        $this->assertFalse($dynamic->getClient()->isVerified());
    }

    /**
     * A client entity contributed by something other than this bundle is not a
     * ClientEntity, and must not be presented to the user as verified.
     */
    public function testForeignClientEntityIsNotVerified(): void
    {
        $client = $this->makeEmpty(ClientEntityInterface::class, [
            'getIdentifier' => self::CLIENT_ID,
            'getName' => 'Foreign',
            'getRedirectUri' => self::REDIRECT_URI,
        ]);

        $consent = $this->hydrator->hydrate('a1b2c3', $this->authorizationRequest($client, []), null);

        $this->assertFalse($consent->getClient()->isVerified());
    }

    public function testRedirectHostFallsBackToTheClientWhenTheRequestNamesNone(): void
    {
        $request = $this->authorizationRequest($this->preRegisteredClient(), []);
        $request->setRedirectUri(null);

        $consent = $this->hydrator->hydrate('a1b2c3', $request, null);

        $this->assertSame('oauth.tools', $consent->getClient()->getRedirectHost());
    }

    /**
     * league models one redirect URI as a string and several as a list, and both shapes
     * reach the hydrator.
     */
    public function testRedirectHostFallsBackToTheFirstOfSeveralClientUris(): void
    {
        $client = new ClientEntity(
            self::CLIENT_ID,
            'My Dev Pimcore',
            ['https://first.example.com/callback', 'https://second.example.com/callback'],
            preRegistered: true,
        );
        $request = $this->authorizationRequest($client, []);
        $request->setRedirectUri(null);

        $consent = $this->hydrator->hydrate('a1b2c3', $request, null);

        $this->assertSame('first.example.com', $consent->getClient()->getRedirectHost());
    }

    public function testUnknownUserIsReportedAsNone(): void
    {
        $consent = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), []),
            null,
        );

        $this->assertNull($consent->getUser());
    }

    /**
     * Pimcore's user model makes both accessors nullable. A user without an id cannot be
     * named as the one who would be acting, so it is reported as none rather than as a
     * user with invented values.
     */
    public function testUserWithoutAnIdIsReportedAsNone(): void
    {
        $consent = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), []),
            $this->user(null, 'admin'),
        );

        $this->assertNull($consent->getUser());
    }

    public function testUserWithoutANameStillHasAnId(): void
    {
        $consent = $this->hydrator->hydrate(
            'a1b2c3',
            $this->authorizationRequest($this->preRegisteredClient(), []),
            $this->user(22, null),
        );

        $this->assertNotNull($consent->getUser());
        $this->assertSame(22, $consent->getUser()->getId());
        $this->assertSame('', $consent->getUser()->getUsername());
    }

    private function preRegisteredClient(): ClientEntity
    {
        return new ClientEntity(
            self::CLIENT_ID,
            'My Dev Pimcore',
            self::REDIRECT_URI,
            preRegistered: true,
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function authorizationRequest(ClientEntityInterface $client, array $scopes): AuthorizationRequest
    {
        $request = new AuthorizationRequest();
        $request->setClient($client);
        $request->setRedirectUri(self::REDIRECT_URI);
        $request->setScopes(array_map(
            static fn (string $scope): ScopeEntityInterface => new ScopeEntity($scope),
            $scopes,
        ));

        return $request;
    }

    private function user(?int $id, ?string $name): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'getId' => $id,
            'getName' => $name,
        ]);
    }
}
