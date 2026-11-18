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
use Pimcore\Bundle\StudioBackendBundle\OAuth\EventSubscriber\OAuthCorsSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class OAuthCorsSubscriberTest extends Unit
{
    private const string MCP_PATH = '/pimcore-mcp/studio/my-server';

    private const string MCP_CLIENT = 'https://client.example';

    /**
     * @param list<string> $allowedOrigins
     */
    private function subscriber(bool $enabled = true, array $allowedOrigins = []): OAuthCorsSubscriber
    {
        return new OAuthCorsSubscriber($enabled, $allowedOrigins);
    }

    private function request(string $path, string $method = 'GET', ?string $origin = null): Request
    {
        $request = Request::create($path, $method);
        if ($origin !== null) {
            $request->headers->set('Origin', $origin);
        }

        return $request;
    }

    private function requestEvent(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createStub(HttpKernelInterface::class), $request, $type);
    }

    private function responseEvent(
        Request $request,
        Response $response,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), $request, $type, $response);
    }

    public function testOptionsPreflightOnOAuthPathShortCircuitsWith204AndCors(): void
    {
        $event = $this->requestEvent($this->request('/pimcore-oauth/token', 'OPTIONS', 'http://localhost:6274'));

        $this->subscriber()->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Authorization', (string) $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function testResponseOnOAuthPathGetsWildcardOrigin(): void
    {
        $request = $this->request('/.well-known/oauth-authorization-server');
        $event = $this->responseEvent($request, new Response('{}'));

        $this->subscriber()->onKernelResponse($event);

        $this->assertSame('*', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('WWW-Authenticate', $event->getResponse()->headers->get('Access-Control-Expose-Headers'));
    }

    public function testMcpPreflightIsAnsweredWithTheStatelessEraHeaders(): void
    {
        $event = $this->requestEvent($this->request(self::MCP_PATH, 'OPTIONS', self::MCP_CLIENT));

        $this->subscriber()->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $response = $event->getResponse();
        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));

        $allowHeaders = strtolower((string) $response->headers->get('Access-Control-Allow-Headers'));
        $allowed = array_map('trim', explode(',', $allowHeaders));
        $expected = [
            'authorization', 'content-type', 'accept', 'last-event-id',
            'mcp-protocol-version', 'mcp-session-id', 'mcp-method', 'mcp-name',
        ];
        foreach ($expected as $header) {
            $this->assertContains($header, $allowed);
        }
    }

    public function testMcpPreflightIsAnsweredOnTheAgentEndpointsToo(): void
    {
        $event = $this->requestEvent($this->request('/pimcore-mcp/agent/meta', 'OPTIONS', self::MCP_CLIENT));

        $this->subscriber()->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
        $this->assertStringContainsString(
            'Mcp-Name',
            (string) $event->getResponse()->headers->get('Access-Control-Allow-Headers')
        );
    }

    public function testMcpResponseExposesTheSessionId(): void
    {
        $request = $this->request(self::MCP_PATH, 'POST', self::MCP_CLIENT);
        $event = $this->responseEvent($request, new Response('{}', Response::HTTP_UNAUTHORIZED));

        $this->subscriber()->onKernelResponse($event);

        $headers = $event->getResponse()->headers;
        $this->assertSame('*', $headers->get('Access-Control-Allow-Origin'));
        $this->assertSame(
            'Mcp-Session-Id, WWW-Authenticate, Retry-After',
            $headers->get('Access-Control-Expose-Headers')
        );
    }

    public function testMcpPreflightFollowsTheAllowList(): void
    {
        $subscriber = $this->subscriber(allowedOrigins: ['https://app.example']);

        $listed = $this->requestEvent($this->request(self::MCP_PATH, 'OPTIONS', 'https://app.example'));
        $subscriber->onKernelRequest($listed);
        $this->assertSame(
            'https://app.example',
            $listed->getResponse()->headers->get('Access-Control-Allow-Origin')
        );

        $unlisted = $this->requestEvent($this->request(self::MCP_PATH, 'OPTIONS', 'https://evil.example'));
        $subscriber->onKernelRequest($unlisted);
        $this->assertFalse($unlisted->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    public function testMcpPathIsUntouchedWhenDisabled(): void
    {
        $optionsEvent = $this->requestEvent($this->request(self::MCP_PATH, 'OPTIONS'));
        $this->subscriber(enabled: false)->onKernelRequest($optionsEvent);
        $this->assertFalse($optionsEvent->hasResponse());

        $responseEvent = $this->responseEvent($this->request(self::MCP_PATH), new Response());
        $this->subscriber(enabled: false)->onKernelResponse($responseEvent);
        $this->assertFalse($responseEvent->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    public function testEncodedMcpPathIsRecognisedLikeTheRouterSeesIt(): void
    {
        $event = $this->requestEvent($this->request('/%70imcore-mcp/studio/s', 'OPTIONS'));

        $this->subscriber()->onKernelRequest($event);

        $this->assertTrue($event->hasResponse());
    }

    public function testNonOAuthPathIsUntouched(): void
    {
        $optionsEvent = $this->requestEvent($this->request('/pimcore-studio/api/assets', 'OPTIONS'));
        $this->subscriber()->onKernelRequest($optionsEvent);
        $this->assertFalse($optionsEvent->hasResponse());

        $responseEvent = $this->responseEvent($this->request('/pimcore-studio/api/assets'), new Response());
        $this->subscriber()->onKernelResponse($responseEvent);
        $this->assertFalse($responseEvent->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    public function testDisabledDoesNothing(): void
    {
        $optionsEvent = $this->requestEvent($this->request('/pimcore-oauth/token', 'OPTIONS'));
        $this->subscriber(enabled: false)->onKernelRequest($optionsEvent);
        $this->assertFalse($optionsEvent->hasResponse());

        $responseEvent = $this->responseEvent($this->request('/pimcore-oauth/token'), new Response());
        $this->subscriber(enabled: false)->onKernelResponse($responseEvent);
        $this->assertFalse($responseEvent->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    public function testAllowListEchoesMatchingOriginWithVary(): void
    {
        $request = $this->request('/pimcore-oauth/token', 'GET', 'https://app.example');
        $event = $this->responseEvent($request, new Response());

        $this->subscriber(allowedOrigins: ['https://app.example'])->onKernelResponse($event);

        $this->assertSame('https://app.example', $event->getResponse()->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('Origin', $event->getResponse()->headers->get('Vary'));
    }

    public function testAllowListRejectsUnlistedOrigin(): void
    {
        $request = $this->request('/pimcore-oauth/token', 'GET', 'https://evil.example');
        $event = $this->responseEvent($request, new Response());

        $this->subscriber(allowedOrigins: ['https://app.example'])->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    /**
     * The router decodes the path once before matching, so "/%70imcore-oauth/token" is
     * routed to the token endpoint. This subscriber and the endpoint guard next to it have
     * to agree on which requests are OAuth requests, and they agree by matching the same
     * decoded value: matched raw, a browser client that percent-encoded would have its
     * token exchange served and then blocked for want of CORS headers.
     */
    public function testEncodedOAuthPathIsRecognisedLikeTheRouterSeesIt(): void
    {
        $optionsEvent = $this->requestEvent($this->request('/%70imcore-oauth/token', 'OPTIONS'));
        $this->subscriber()->onKernelRequest($optionsEvent);
        $this->assertTrue($optionsEvent->hasResponse());

        $responseEvent = $this->responseEvent($this->request('/%70imcore-oauth/token'), new Response());
        $this->subscriber()->onKernelResponse($responseEvent);
        $this->assertSame('*', $responseEvent->getResponse()->headers->get('Access-Control-Allow-Origin'));
    }

    /**
     * Decoded exactly once, like the router. Decoding repeatedly would claim paths the
     * router never routes here.
     */
    public function testDoubleEncodedPathIsNotClaimed(): void
    {
        $event = $this->requestEvent($this->request('/%2570imcore-oauth/token', 'OPTIONS'));

        $this->subscriber()->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }

    /**
     * The consent API is a Studio API endpoint: cookie-authenticated, under the Studio
     * prefix, and answered by the Studio CORS subscriber with its credentialed policy.
     * This one must not reach it with a wildcard origin.
     */
    public function testConsentApiIsNotGivenTheWildcardPolicy(): void
    {
        $event = $this->responseEvent(
            $this->request('/pimcore-studio/api/oauth/authorizations/a1b2c3'),
            new Response(),
        );

        $this->subscriber()->onKernelResponse($event);

        $this->assertFalse($event->getResponse()->headers->has('Access-Control-Allow-Origin'));
    }

    public function testSubRequestIsIgnored(): void
    {
        $event = $this->requestEvent(
            $this->request('/pimcore-oauth/token', 'OPTIONS'),
            HttpKernelInterface::SUB_REQUEST,
        );

        $this->subscriber()->onKernelRequest($event);

        $this->assertFalse($event->hasResponse());
    }
}
