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
use Http\Discovery\Psr17Factory;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Event\AuthorizationConsentEvent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Hydrator\AuthorizationConsentHydrator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationRequestValidatorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ClientEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\ScopeEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\AuthorizationConsentService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class AuthorizationConsentServiceTest extends Unit
{
    private const string AUTHORIZATION_ID = 'a1b2c3';

    private const string ISSUER = 'https://pimcore.example.com';

    private const string REDIRECT = 'https://oauth.tools/callback/code?code=abc&state=xyz';

    /** @var list<array{object, string}> */
    private array $dispatched = [];

    /** @var list<string> */
    private array $removed = [];

    public function _before(): void
    {
        $this->dispatched = [];
        $this->removed = [];
    }

    public function testGetConsentReturnsTheHydratedPayload(): void
    {
        $consent = $this->service()->getConsent(self::AUTHORIZATION_ID);

        $this->assertSame(self::AUTHORIZATION_ID, $consent->getAuthorizationId());
        $this->assertSame('my-dev-pimcore', $consent->getClient()->getIdentifier());
        $this->assertSame(['test:read'], $consent->getScopes());
        $this->assertNotNull($consent->getUser());
        $this->assertSame(22, $consent->getUser()->getId());
    }

    /**
     * The bundle answers every read model with a pre-response event, and an integration
     * has a legitimate reason to enrich how a client is presented on the screen.
     */
    public function testGetConsentDispatchesThePreResponseEvent(): void
    {
        $consent = $this->service()->getConsent(self::AUTHORIZATION_ID);

        $this->assertCount(1, $this->dispatched);
        [$event, $eventName] = $this->dispatched[0];
        $this->assertInstanceOf(AuthorizationConsentEvent::class, $event);
        $this->assertSame(AuthorizationConsentEvent::EVENT_NAME, $eventName);
        $this->assertSame($consent, $event->getConsent());

        // A pre-response event exists so a listener can add to the payload, and this is
        // the only thing it may change about a consent screen.
        $event->addAdditionalAttribute('clientLogo', 'https://example.com/logo.png');
        $this->assertSame('https://example.com/logo.png', $consent->getAdditionalAttribute('clientLogo'));
    }

    public function testGetConsentIsNotFoundWhenNothingIsPending(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service(storedParams: null)->getConsent(self::AUTHORIZATION_ID);
    }

    /**
     * The stored parameters are re-validated rather than unserialized, so a pending
     * authorization that no longer describes a valid request is gone, not stale.
     */
    public function testGetConsentIsNotFoundWhenTheStoredRequestNoLongerValidates(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service(requestValidates: false)->getConsent(self::AUTHORIZATION_ID);
    }

    public function testCompleteConsentReturnsTheRedirectWithTheIssuer(): void
    {
        $redirect = $this->service()->completeConsent(self::AUTHORIZATION_ID, true);

        $this->assertSame(
            self::REDIRECT . '&iss=' . rawurlencode(self::ISSUER),
            $redirect->getLocation(),
        );
    }

    public function testCompleteConsentAddsNoIssuerWhenNoneIsConfigured(): void
    {
        $redirect = $this->service(issuer: null)->completeConsent(self::AUTHORIZATION_ID, true);

        $this->assertSame(self::REDIRECT, $redirect->getLocation());
    }

    /**
     * A denial is not an error: league raises access_denied, which becomes the redirect
     * that tells the client the answer.
     */
    public function testDenialBecomesTheAccessDeniedRedirect(): void
    {
        $service = $this->service(
            completion: static fn (): never => throw OAuthServerException::accessDenied(
                null,
                'https://oauth.tools/callback/code?state=xyz',
            ),
        );

        $redirect = $service->completeConsent(self::AUTHORIZATION_ID, false);

        $this->assertStringContainsString('error=access_denied', $redirect->getLocation());
        $this->assertStringContainsString('iss=' . rawurlencode(self::ISSUER), $redirect->getLocation());
    }

    /**
     * Single use: the pending authorization is gone whichever way the user answered, so a
     * replayed consent id cannot mint a second code.
     *
     * @dataProvider decisionProvider
     */
    public function testCompletingConsumesThePendingAuthorization(bool $approved): void
    {
        $this->service()->completeConsent(self::AUTHORIZATION_ID, $approved);

        $this->assertSame([self::AUTHORIZATION_ID], $this->removed);
    }

    /**
     * Deliberate, and the reasoning is in the service: this value is where the browser
     * carries the authorization code, league has just validated it against the client's
     * registered redirect URIs, and a listener able to rewrite it would undo that check
     * after the fact. If an event is ever added here, that decision is being reversed.
     */
    public function testCompleteConsentDispatchesNoEvent(): void
    {
        $this->service()->completeConsent(self::AUTHORIZATION_ID, true);

        $this->assertSame([], $this->dispatched);
    }

    public function testCompleteConsentIsNotFoundWithoutAnIdentifiableUser(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service(hasUser: false)->completeConsent(self::AUTHORIZATION_ID, true);
    }

    public function testCompleteConsentIsNotFoundWhenTheUserHasNoId(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service(userId: null)->completeConsent(self::AUTHORIZATION_ID, true);
    }

    public function testCompleteConsentIsNotFoundWhenNothingIsPending(): void
    {
        $this->expectException(NotFoundException::class);

        $this->service(storedParams: null)->completeConsent(self::AUTHORIZATION_ID, true);
    }

    /**
     * The screen and the approval both re-validate from the pinned parameters, so the
     * scope is written back as league narrowed it. Left as the client sent it, a
     * configuration change in between would grant more than the screen showed.
     */
    public function testPinConsentParametersWritesBackTheNarrowedScope(): void
    {
        $pinned = $this->service()->pinConsentParameters(
            ['client_id' => 'my-dev-pimcore', 'scope' => 'test:read test:write'],
            $this->authorizationRequest(['test:read']),
        );

        $this->assertSame('test:read', $pinned['scope']);
        $this->assertSame('my-dev-pimcore', $pinned['client_id']);
    }

    public function testPinConsentParametersLeavesTheParametersAloneWithoutScopes(): void
    {
        $params = ['client_id' => 'my-dev-pimcore', 'state' => 'xyz'];

        $this->assertSame(
            $params,
            $this->service()->pinConsentParameters($params, $this->authorizationRequest([])),
        );
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public function decisionProvider(): iterable
    {
        yield 'approved' => [true];
        yield 'denied' => [false];
    }

    /**
     * @param array<string, mixed>|null $storedParams
     */
    private function service(
        ?array $storedParams = ['client_id' => 'my-dev-pimcore'],
        bool $requestValidates = true,
        bool $hasUser = true,
        ?int $userId = 22,
        ?string $issuer = self::ISSUER,
        ?callable $completion = null,
    ): AuthorizationConsentService {
        return new AuthorizationConsentService(
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => function (object $event, ?string $eventName = null): object {
                    $this->dispatched[] = [$event, (string) $eventName];

                    return $event;
                },
            ]),
            $this->makeEmpty(PendingAuthorizationStoreInterface::class, [
                'get' => $storedParams,
                'remove' => function (string $id): void {
                    $this->removed[] = $id;
                },
            ]),
            $this->makeEmpty(AuthorizationRequestValidatorInterface::class, [
                'validate' => $requestValidates ? $this->authorizationRequest(['test:read']) : null,
            ]),
            $this->makeEmpty(AuthorizationServerFactoryInterface::class, [
                'create' => $this->makeEmpty(AuthorizationServer::class, [
                    'completeAuthorizationRequest' => $completion ?? static fn (
                        AuthorizationRequestInterface $request,
                        ResponseInterface $response,
                    ): ResponseInterface => $response->withHeader('Location', self::REDIRECT),
                ]),
            ]),
            new AuthorizationConsentHydrator(),
            new Psr17Factory(),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $hasUser
                    ? $this->user($userId)
                    : static fn (): never => throw new UserNotFoundException(),
            ]),
            $issuer,
        );
    }

    /**
     * @param list<string> $scopes
     */
    private function authorizationRequest(array $scopes): AuthorizationRequest
    {
        $request = new AuthorizationRequest();
        $request->setClient(new ClientEntity(
            'my-dev-pimcore',
            'My Dev Pimcore',
            'https://oauth.tools/callback/code',
            preRegistered: true,
        ));
        $request->setRedirectUri('https://oauth.tools/callback/code');
        $request->setScopes(array_map(
            static fn (string $scope): ScopeEntityInterface => new ScopeEntity($scope),
            $scopes,
        ));

        return $request;
    }

    private function user(?int $id): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, [
            'getId' => $id,
            'getName' => 'admin',
        ]);
    }
}
