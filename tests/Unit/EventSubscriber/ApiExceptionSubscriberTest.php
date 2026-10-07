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
use Pimcore\Bundle\StudioBackendBundle\EventSubscriber\ApiExceptionSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\RateLimitException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Throwable;

/**
 * @internal
 */
final class ApiExceptionSubscriberTest extends Unit
{
    private const string URL_PREFIX = '/pimcore-studio/api';

    private const string MCP_PATH = '/pimcore-mcp/agent/documents';

    public function testStudioPathExceptionIsConvertedToTheJsonEnvelope(): void
    {
        $event = $this->createEvent('/pimcore-studio/api/assets/1', new NotFoundHttpException('gone'));

        $this->createSubscriber()->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(404, $event->getResponse()->getStatusCode());
    }

    /**
     * The rate limiter now covers MCP paths, so its 429 has to reach the same envelope
     * every other Studio error uses rather than Symfony's default error rendering.
     */
    public function testMcpRateLimitExceptionIsConvertedToTheJsonEnvelope(): void
    {
        $event = $this->createEvent(self::MCP_PATH, new RateLimitException());

        $this->createSubscriber()->onKernelException($event);

        $this->assertNotNull($event->getResponse());
        $this->assertSame(429, $event->getResponse()->getStatusCode());
    }

    /**
     * MCP is JSON-RPC and owns its own error shapes. Only the exception this bundle raises
     * there is claimed; anything the MCP server itself produces must pass through untouched.
     */
    public function testOtherMcpExceptionsArePassedThrough(): void
    {
        $event = $this->createEvent(self::MCP_PATH, new NotFoundHttpException('unknown tool group'));

        $this->createSubscriber()->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    /**
     * The OAuth registration endpoint is outside the Studio API prefix, so before the
     * limiter covered it this exception fell through to Symfony's default error renderer:
     * the status was right, the body was an HTML error page, and in dev a stack trace on a
     * public unauthenticated endpoint. RFC 7591 clients parse JSON.
     */
    public function testOAuthRegisterRateLimitExceptionIsConvertedToTheJsonEnvelope(): void
    {
        $event = $this->createEvent('/pimcore-oauth/register', new RateLimitException());

        $this->createSubscriber()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $this->assertSame(
            'application/json',
            explode(';', (string) $response->headers->get('Content-Type'))[0],
        );

        $body = json_decode((string) $response->getContent(), true);
        $this->assertIsArray($body);
        $this->assertArrayHasKey('message', $body);
        $this->assertSame('Rate limit exceeded. Please try again later.', $body['message']);
    }

    /**
     * Encoded the way the rate limiter matches it, so the two agree on what counts as the
     * registration endpoint.
     */
    public function testEncodedOAuthRegisterPathIsAlsoConverted(): void
    {
        $event = $this->createEvent('/pimcore-oauth/%72egister', new RateLimitException());

        $this->createSubscriber()->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
    }

    /**
     * A foreign exception outside the Studio API still belongs to whoever serves that
     * path. Only this bundle's own RateLimitException is claimed everywhere, because only
     * this bundle raises it.
     */
    public function testForeignExceptionOnANonStudioPathIsIgnored(): void
    {
        $event = $this->createEvent('/some/other/path', new NotFoundHttpException('not ours'));

        $this->createSubscriber()->onKernelException($event);

        $this->assertNull($event->getResponse());
    }

    private function createSubscriber(): ApiExceptionSubscriber
    {
        return new ApiExceptionSubscriber('prod', self::URL_PREFIX);
    }

    private function createEvent(string $path, Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createKernelStub(),
            Request::create($path, 'POST'),
            HttpKernelInterface::MAIN_REQUEST,
            $exception
        );
    }

    private function createKernelStub(): HttpKernelInterface
    {
        return new class() implements HttpKernelInterface {
            public function handle(
                Request $request,
                int $type = self::MAIN_REQUEST,
                bool $catch = true
            ): Response {
                return new Response();
            }
        };
    }
}
