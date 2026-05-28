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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\EventSubscriber;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\RateLimitSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * @internal
 */
final class RateLimitSubscriberTest extends Unit
{
    private const string URL_PREFIX = '/pimcore-studio/api';

    public function testGetSubscribedEvents(): void
    {
        $events = RateLimitSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame(['onKernelRequest', 200], $events[KernelEvents::REQUEST]);
        $this->assertSame(['onKernelResponse', -10], $events[KernelEvents::RESPONSE]);
    }

    /**
     * @throws Exception
     */
    public function testRequestIsRateLimitedOnStudioPath(): void
    {
        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        $subscriber->onKernelRequest($event);

        $rateLimit = $event->getRequest()->attributes->get('_studio_rate_limit');
        $this->assertInstanceOf(RateLimit::class, $rateLimit);
        $this->assertSame(499, $rateLimit->getRemainingTokens());
    }

    /**
     * @throws Exception
     */
    public function testRequestThrowsRateLimitExceptionWhenExceeded(): void
    {
        $subscriber = $this->createSubscriber(accepted: false, remaining: 0, limit: 500);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        $this->expectException(RateLimitException::class);
        $subscriber->onKernelRequest($event);
    }

    /**
     * @throws Exception
     */
    public function testRateLimitAttributeIsSetBeforeExceptionIsThrown(): void
    {
        $subscriber = $this->createSubscriber(accepted: false, remaining: 0, limit: 500);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        try {
            $subscriber->onKernelRequest($event);
        } catch (RateLimitException) {
            // expected
        }

        $rateLimit = $event->getRequest()->attributes->get('_studio_rate_limit');
        $this->assertInstanceOf(RateLimit::class, $rateLimit);
        $this->assertSame(0, $rateLimit->getRemainingTokens());
    }

    /**
     * @throws Exception
     */
    public function testNonStudioPathIsIgnored(): void
    {
        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $event = $this->createRequestEvent('/admin/some-route');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testOptionsRequestIsIgnored(): void
    {
        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1', 'OPTIONS');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testDisabledSubscriberSkipsRequest(): void
    {
        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500, enabled: false);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testSubRequestIsIgnored(): void
    {
        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1', 'GET', false);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testResponseHeadersAreSet(): void
    {
        $retryAfter = new DateTimeImmutable('2026-01-01 00:00:00');
        $rateLimit = new RateLimit(499, $retryAfter, true, 500);

        $request = Request::create('/pimcore-studio/api/assets/1');
        $request->attributes->set('_studio_rate_limit', $rateLimit);

        $response = new Response();
        $event = $this->createResponseEvent($request, $response);

        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $subscriber->onKernelResponse($event);

        $this->assertSame('500', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('499', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertSame((string) $retryAfter->getTimestamp(), $response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * @throws Exception
     */
    public function testResponseHeadersOnExceededRequest(): void
    {
        $retryAfter = new DateTimeImmutable('2026-01-01 00:01:00');
        $rateLimit = new RateLimit(0, $retryAfter, false, 500);

        $request = Request::create('/pimcore-studio/api/assets/1');
        $request->attributes->set('_studio_rate_limit', $rateLimit);

        $response = new Response('', 429);
        $event = $this->createResponseEvent($request, $response);

        $subscriber = $this->createSubscriber(accepted: false, remaining: 0, limit: 500);
        $subscriber->onKernelResponse($event);

        $this->assertSame('500', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertSame((string) $retryAfter->getTimestamp(), $response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * @throws Exception
     */
    public function testResponseWithoutRateLimitAttributeIsNotModified(): void
    {
        $request = Request::create('/pimcore-studio/api/assets/1');
        $response = new Response();
        $event = $this->createResponseEvent($request, $response);

        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500);
        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
        $this->assertFalse($response->headers->has('X-RateLimit-Remaining'));
        $this->assertFalse($response->headers->has('X-RateLimit-Reset'));
    }

    /**
     * @throws Exception
     */
    public function testDisabledSubscriberSkipsResponse(): void
    {
        $retryAfter = new DateTimeImmutable('2026-01-01 00:00:00');
        $rateLimit = new RateLimit(499, $retryAfter, true, 500);

        $request = Request::create('/pimcore-studio/api/assets/1');
        $request->attributes->set('_studio_rate_limit', $rateLimit);

        $response = new Response();
        $event = $this->createResponseEvent($request, $response);

        $subscriber = $this->createSubscriber(accepted: true, remaining: 499, limit: 500, enabled: false);
        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    /**
     * @throws Exception
     */
    private function createSubscriber(
        bool $accepted,
        int $remaining,
        int $limit,
        bool $enabled = true,
    ): RateLimitSubscriber {
        $rateLimit = new RateLimit(
            $remaining,
            new DateTimeImmutable('+1 minute'),
            $accepted,
            $limit,
        );

        $limiter = $this->makeEmpty(LimiterInterface::class, [
            'consume' => $rateLimit,
        ]);

        $factory = $this->makeEmpty(RateLimiterFactoryInterface::class, [
            'create' => $limiter,
        ]);

        return new RateLimitSubscriber(
            self::URL_PREFIX,
            $factory,
            $enabled,
        );
    }

    /**
     * @throws Exception
     */
    private function createRequestEvent(
        string $path,
        string $method = 'GET',
        bool $isMainRequest = true,
    ): RequestEvent {
        $request = Request::create($path, $method);
        $kernel = $this->makeEmpty(HttpKernelInterface::class);

        return new RequestEvent(
            $kernel,
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }

    /**
     * @throws Exception
     */
    private function createResponseEvent(
        Request $request,
        Response $response,
    ): ResponseEvent {
        $kernel = $this->makeEmpty(HttpKernelInterface::class);

        return new ResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }
}
