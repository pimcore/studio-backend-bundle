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

    public function testNonStudioPathIsIgnored(): void
    {
        $event = $this->createEvent('/some/other/path', new RateLimitException());

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
