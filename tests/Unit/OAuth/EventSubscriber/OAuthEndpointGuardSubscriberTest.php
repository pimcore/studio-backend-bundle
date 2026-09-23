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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OAuth\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\OAuth\EventSubscriber\OAuthEndpointGuardSubscriber;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthEndpointGuardSubscriberTest extends Unit
{
    private const string API_PREFIX = '/pimcore-studio/api';

    private const string ISSUER = 'https://pimcore.example.com';

    private function subscriber(
        bool $enabled = false,
        string $apiPrefix = self::API_PREFIX,
        ?string $issuer = self::ISSUER,
        ?LoggerInterface $logger = null,
    ): OAuthEndpointGuardSubscriber {
        return new OAuthEndpointGuardSubscriber($enabled, $apiPrefix, $issuer, $logger);
    }

    private function requestEvent(
        string $path,
        string $method = 'GET',
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): RequestEvent {
        return new RequestEvent(
            $this->createStub(HttpKernelInterface::class),
            Request::create($path, $method),
            $type,
        );
    }

    /**
     * Every OAuth endpoint has to disappear while the server is off. The token and
     * authorize paths are the ones that would otherwise answer 500 on a public,
     * unauthenticated route; register is included because its controller only
     * consults its own sub-flag.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function oauthPathProvider(): array
    {
        return [
            'token' => ['/pimcore-oauth/token', 'POST'],
            'authorize' => ['/pimcore-oauth/authorize', 'GET'],
            'register' => ['/pimcore-oauth/register', 'POST'],
            'authorization server metadata' => ['/.well-known/oauth-authorization-server', 'GET'],
            'protected resource metadata' => ['/.well-known/oauth-protected-resource/anything', 'GET'],
            'consent details' => [self::API_PREFIX . '/oauth/authorizations/abc', 'GET'],
            'consent approval' => [self::API_PREFIX . '/oauth/authorizations/abc', 'POST'],
        ];
    }

    /**
     * @dataProvider oauthPathProvider
     */
    public function testRefusesOAuthPathsWhileDisabled(string $path, string $method): void
    {
        $event = $this->requestEvent($path, $method);

        $this->subscriber()->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response, "$path must not reach routing while OAuth is disabled");
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * @dataProvider oauthPathProvider
     */
    public function testLeavesOAuthPathsAloneWhileEnabled(string $path, string $method): void
    {
        $event = $this->requestEvent($path, $method);

        $this->subscriber(enabled: true)->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * An issuer taken from an environment variable is only known at runtime, so the build
     * cannot check its shape. Serving OAuth with a malformed one issues tokens and metadata
     * whose URIs look plausible and never match, so the endpoints refuse instead, naming the
     * cause in the log rather than in the public response.
     *
     * @dataProvider oauthPathProvider
     */
    public function testRefusesOAuthPathsWhileTheIssuerIsMalformed(string $path, string $method): void
    {
        $event = $this->requestEvent($path, $method);
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('must be a bare origin'));

        $this->subscriber(enabled: true, issuer: 'https://pimcore.example.com/', logger: $logger)
            ->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response, "$path must not be served with a malformed issuer");
        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        $this->assertSame(['error' => 'server_error'], json_decode((string) $response->getContent(), true));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function malformedIssuerProvider(): array
    {
        return [
            'empty' => [''],
            'trailing slash' => ['https://pimcore.example.com/'],
            'not absolute' => ['pimcore.example.com'],
            'uppercase host' => ['https://PIMCORE.example.com'],
        ];
    }

    /**
     * @dataProvider malformedIssuerProvider
     */
    public function testRefusesTheTokenEndpointForEveryMalformedIssuer(string $issuer): void
    {
        $event = $this->requestEvent('/pimcore-oauth/token', 'POST');

        $this->subscriber(enabled: true, issuer: $issuer)->onKernelRequest($event);

        $this->assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $event->getResponse()?->getStatusCode());
    }

    public function testLeavesUnrelatedPathsAloneWhileTheIssuerIsMalformed(): void
    {
        $event = $this->requestEvent('/pimcore-studio/api/assets/1');

        $this->subscriber(enabled: true, issuer: 'https://pimcore.example.com/')->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * Disabled wins: the endpoints do not exist, whatever the issuer.
     */
    public function testAMalformedIssuerStillAnswersNotFoundWhileDisabled(): void
    {
        $event = $this->requestEvent('/pimcore-oauth/token', 'POST');

        $this->subscriber(issuer: 'https://pimcore.example.com/')->onKernelRequest($event);

        $this->assertSame(Response::HTTP_NOT_FOUND, $event->getResponse()?->getStatusCode());
    }

    /**
     * Security regression. `Request::getPathInfo()` is still percent-encoded while the
     * router matches on the decoded path, so comparing the raw value let
     * `/%70imcore-oauth/register` route to the registration controller with this guard
     * skipped entirely. The set of encodings is unbounded, so the fix has to be decoding
     * rather than a list of variants.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function encodedOAuthPathProvider(): array
    {
        return [
            'encoded prefix' => ['/%70imcore-oauth/register', 'POST'],
            'encoded endpoint' => ['/pimcore-oauth/%72egister', 'POST'],
            'encoded token endpoint' => ['/pimcore-oauth/%74oken', 'POST'],
            'encoded well-known' => ['/.well-known/%6fauth-authorization-server', 'GET'],
            'encoded api prefix' => [self::API_PREFIX . '/%6fauth/authorizations/abc', 'POST'],
        ];
    }

    /**
     * @dataProvider encodedOAuthPathProvider
     */
    public function testRefusesPercentEncodedOAuthPathsWhileDisabled(string $path, string $method): void
    {
        $event = $this->requestEvent($path, $method);

        $this->subscriber()->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response, "$path must not reach routing while OAuth is disabled");
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    /**
     * Decoded exactly once, like the router. A doubly-encoded path decodes to
     * `/%70imcore-oauth/register`, which the router does not route to an OAuth endpoint,
     * so refusing it here would 404 a request that was never going to arrive.
     */
    public function testDoesNotClaimDoublyEncodedPaths(): void
    {
        $event = $this->requestEvent('/%2570imcore-oauth/register', 'POST');

        $this->subscriber()->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unrelatedPathProvider(): array
    {
        return [
            'studio api' => [self::API_PREFIX . '/assets/1'],
            'other well-known' => ['/.well-known/security.txt'],
            'mcp' => ['/pimcore-mcp/agent/something'],
            'frontend' => ['/'],
            // Shares a prefix with the OAuth path but is a different endpoint.
            'lookalike' => ['/pimcore-oauth-something'],
        ];
    }

    /**
     * @dataProvider unrelatedPathProvider
     */
    public function testLeavesUnrelatedPathsAloneWhileDisabled(string $path): void
    {
        $event = $this->requestEvent($path);

        $this->subscriber()->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testIgnoresSubRequests(): void
    {
        $event = $this->requestEvent('/pimcore-oauth/token', 'POST', HttpKernelInterface::SUB_REQUEST);

        $this->subscriber()->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * An unset Studio API prefix must not collapse into a bare "/oauth/" match that
     * could belong to the host application.
     */
    public function testDoesNotClaimBareOAuthPathWithoutApiPrefix(): void
    {
        $event = $this->requestEvent('/oauth/authorizations/abc');

        $this->subscriber(apiPrefix: '')->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testRunsBeforeTheRouter(): void
    {
        $events = OAuthEndpointGuardSubscriber::getSubscribedEvents();

        // RouterListener subscribes at 32; the guard must win so a disabled endpoint
        // is refused before routing and the firewall see it.
        $this->assertGreaterThan(32, $events[\Symfony\Component\HttpKernel\KernelEvents::REQUEST][1]);
    }
}
