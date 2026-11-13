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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Controller;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Controller\McpServerController;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerAccess;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Dto\McpServerDefinition;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InsufficientMcpScopeException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Repository\McpServerConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpServerAccessResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpToolCallScopeGuard;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpToolCallScopeGuardInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Server\McpServerFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\NullLogger;
use stdClass;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use function json_encode;

/**
 * Wiring of the scope guard in the controller: what the controller hands to the guard and how a refusal ends.
 * The guard's own rules are in McpToolCallScopeGuardTest; the transport and the server are not reached here.
 */
final class McpServerControllerTest extends Unit
{
    private const string SLUG = 'pme-server';

    public function testTokenWithoutWriteScopeIsAnsweredWith403ForAWriteTool(): void
    {
        $controller = $this->controller(new McpToolCallScopeGuard($this->registry()));
        $request = $this->request($this->call('write_tool'), [McpScopes::REQUEST_ATTRIBUTE => ['mcp:read']]);

        $this->expectException(AccessDeniedHttpException::class);
        $this->expectExceptionMessage('mcp:write');

        $controller($request, self::SLUG);
    }

    public function testTheScopesOfTheTokenAndTheBodyReachTheGuard(): void
    {
        $body = $this->call('read_tool');
        $guard = $this->createMock(McpToolCallScopeGuardInterface::class);
        $guard->expects($this->once())
            ->method('assertAllowed')
            ->with($body, ['mcp:read', 'mcp:write'])
            ->willThrowException(InsufficientMcpScopeException::forTool('read_tool', 'mcp:write'));

        $this->expectException(AccessDeniedHttpException::class);

        $this->controller($guard)(
            $this->request($body, [McpScopes::REQUEST_ATTRIBUTE => ['mcp:read', 'mcp:write', 12]]),
            self::SLUG
        );
    }

    public function testRequestWithoutTheScopeAttributeReachesTheGuardAsUnlimited(): void
    {
        $guard = $this->createMock(McpToolCallScopeGuardInterface::class);
        $guard->expects($this->once())
            ->method('assertAllowed')
            ->with($this->anything(), null)
            ->willThrowException(InsufficientMcpScopeException::forUnreadableRequest('mcp:write'));

        $this->expectException(AccessDeniedHttpException::class);

        $this->controller($guard)($this->request($this->call('write_tool'), []), self::SLUG);
    }

    public function testUserWithoutServerAccessIsRefusedBeforeTheGuardRuns(): void
    {
        $guard = $this->createMock(McpToolCallScopeGuardInterface::class);
        $guard->expects($this->never())->method('assertAllowed');

        $this->expectException(AccessDeniedHttpException::class);

        $this->controller($guard, false)($this->request($this->call('read_tool'), []), self::SLUG);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function request(string $body, array $attributes): Request
    {
        return new Request([], [], $attributes, [], [], [], $body);
    }

    private function controller(McpToolCallScopeGuardInterface $guard, bool $mayConnect = true): McpServerController
    {
        $definition = new McpServerDefinition(
            self::SLUG, 'Server', '', ['read_tool', 'write_tool'], [], true, new McpServerAccess()
        );

        $repository = $this->createMock(McpServerConfigRepositoryInterface::class);
        $repository->method('list')->willReturn([$definition]);

        $accessResolver = $this->createMock(McpServerAccessResolverInterface::class);
        $accessResolver->method('isAllowed')->willReturn($mayConnect);

        $security = $this->createMock(SecurityServiceInterface::class);
        $security->method('getCurrentUser')->willReturn($this->createMock(UserInterface::class));

        return new McpServerController(
            $repository,
            $this->createMock(McpServerFactoryInterface::class),
            $accessResolver,
            $guard,
            $security,
            $this->createMock(HttpMessageFactoryInterface::class),
            $this->createMock(HttpFoundationFactoryInterface::class),
            $this->createMock(ResponseFactoryInterface::class),
            $this->createMock(StreamFactoryInterface::class),
            new NullLogger(),
        );
    }

    private function registry(): McpToolRegistry
    {
        $meta = static fn (array $annotations): array => [
            'class' => stdClass::class, 'method' => 'run', 'title' => null,
            'description' => '', 'annotations' => $annotations, 'outputSchema' => null,
        ];

        return new McpToolRegistry([
            'read_tool' => $meta(['readOnlyHint' => true]),
            'write_tool' => $meta(['readOnlyHint' => false]),
        ]);
    }

    private function call(string $tool): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/call',
            'params' => ['name' => $tool, 'arguments' => []],
        ]);
    }
}
