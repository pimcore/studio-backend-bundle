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
use Symfony\Component\RateLimiter\RateLimit;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

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
        $subscriber = $this->createSubscriber();
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
        $subscriber = $this->createSubscriber(limit: 1);
        $firstEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');
        $subscriber->onKernelRequest($firstEvent);

        $this->expectException(RateLimitException::class);
        $secondEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');
        $subscriber->onKernelRequest($secondEvent);
    }

    /**
     * @throws Exception
     */
    public function testRateLimitAttributeIsSetBeforeExceptionIsThrown(): void
    {
        $subscriber = $this->createSubscriber(limit: 1);
        $firstEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');
        $subscriber->onKernelRequest($firstEvent);

        $secondEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        try {
            $subscriber->onKernelRequest($secondEvent);
        } catch (RateLimitException) {
            // expected
        }

        $rateLimit = $secondEvent->getRequest()->attributes->get('_studio_rate_limit');
        $this->assertInstanceOf(RateLimit::class, $rateLimit);
        $this->assertSame(0, $rateLimit->getRemainingTokens());
    }

    /**
     * @throws Exception
     */
    public function testNonStudioPathIsIgnored(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createRequestEvent('/admin/some-route');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testOptionsRequestIsIgnored(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1', 'OPTIONS');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testDisabledSubscriberSkipsRequest(): void
    {
        $subscriber = $this->createSubscriber(enabled: false);
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testSubRequestIsIgnored(): void
    {
        $subscriber = $this->createSubscriber();
        $event = $this->createRequestEvent('/pimcore-studio/api/assets/1', 'GET', false);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getRequest()->attributes->get('_studio_rate_limit'));
    }

    /**
     * @throws Exception
     */
    public function testResponseHeadersAreSet(): void
    {
        $subscriber = $this->createSubscriber();
        $requestEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');
        $subscriber->onKernelRequest($requestEvent);

        $response = new Response();
        $responseEvent = $this->createResponseEvent($requestEvent->getRequest(), $response);
        $subscriber->onKernelResponse($responseEvent);

        $this->assertSame('500', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('499', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * @throws Exception
     */
    public function testResponseHeadersOnExceededRequest(): void
    {
        $subscriber = $this->createSubscriber(limit: 1);
        $firstEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');
        $subscriber->onKernelRequest($firstEvent);

        $secondEvent = $this->createRequestEvent('/pimcore-studio/api/assets/1');

        try {
            $subscriber->onKernelRequest($secondEvent);
        } catch (RateLimitException) {
            // expected
        }

        $response = new Response('', 429);
        $responseEvent = $this->createResponseEvent($secondEvent->getRequest(), $response);
        $subscriber->onKernelResponse($responseEvent);

        $this->assertSame('1', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /**
     * @throws Exception
     */
    public function testResponseWithoutRateLimitAttributeIsNotModified(): void
    {
        $request = Request::create('/pimcore-studio/api/assets/1');
        $response = new Response();
        $event = $this->createResponseEvent($request, $response);

        $subscriber = $this->createSubscriber();
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
        $subscriber = $this->createSubscriber(enabled: false);

        $request = Request::create('/pimcore-studio/api/assets/1');
        $request->attributes->set('_studio_rate_limit', new RateLimit(499, new DateTimeImmutable(), true, 500));

        $response = new Response();
        $event = $this->createResponseEvent($request, $response);
        $subscriber->onKernelResponse($event);

        $this->assertFalse($response->headers->has('X-RateLimit-Limit'));
    }

    private function createSubscriber(
        int $limit = 500,
        bool $enabled = true,
    ): RateLimitSubscriber {
        $factory = new RateLimiterFactory(
            [
                'id' => 'test_studio_api',
                'policy' => 'sliding_window',
                'limit' => $limit,
                'interval' => '60 seconds',
            ],
            new InMemoryStorage(),
        );

        return new RateLimitSubscriber(
            self::URL_PREFIX,
            $factory,
            $enabled,
        );
    }

    private function createRequestEvent(
        string $path,
        string $method = 'GET',
        bool $isMainRequest = true,
    ): RequestEvent {
        $request = Request::create($path, $method);

        return new RequestEvent(
            $this->createKernelStub(),
            $request,
            $isMainRequest ? HttpKernelInterface::MAIN_REQUEST : HttpKernelInterface::SUB_REQUEST,
        );
    }

    private function createResponseEvent(
        Request $request,
        Response $response,
    ): ResponseEvent {
        return new ResponseEvent(
            $this->createKernelStub(),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        );
    }

    private function createKernelStub(): HttpKernelInterface
    {
        return new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
    }
}
