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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthEndpointGuardSubscriberTest extends Unit
{
    private const string API_PREFIX = '/pimcore-studio/api';

    private function subscriber(
        bool $enabled = false,
        string $apiPrefix = self::API_PREFIX,
    ): OAuthEndpointGuardSubscriber {
        return new OAuthEndpointGuardSubscriber($enabled, $apiPrefix);
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
