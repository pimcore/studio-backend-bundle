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
