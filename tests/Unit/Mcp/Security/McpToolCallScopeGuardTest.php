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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Security;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\InsufficientMcpScopeException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Security\McpToolCallScopeGuard;
use stdClass;
use function json_encode;
use function sprintf;

final class McpToolCallScopeGuardTest extends Unit
{
    public function testReadOnlyTokenIsRefusedAWriteTool(): void
    {
        $this->expectException(InsufficientMcpScopeException::class);
        $this->expectExceptionMessage('mcp:write');

        $this->guard()->assertAllowed($this->call('write_tool'), ['mcp:read']);
    }

    public function testReadOnlyTokenIsRefusedAToolWithoutAnnotations(): void
    {
        $this->expectException(InsufficientMcpScopeException::class);

        $this->guard()->assertAllowed($this->call('bare_tool'), ['mcp:read']);
    }

    public function testReadOnlyTokenMayCallAReadTool(): void
    {
        $this->guard()->assertAllowed($this->call('read_tool'), ['mcp:read']);

        $this->addToAssertionCount(1);
    }

    public function testReadAndWriteTokenMayCallAWriteTool(): void
    {
        $this->guard()->assertAllowed($this->call('write_tool'), ['mcp:read', 'mcp:write']);

        $this->addToAssertionCount(1);
    }

    public function testTokenWithoutScopeMayCallAWriteTool(): void
    {
        $this->guard()->assertAllowed($this->call('write_tool'), []);

        $this->addToAssertionCount(1);
    }

    public function testCredentialWithoutScopesMayCallAWriteTool(): void
    {
        $this->guard()->assertAllowed($this->call('write_tool'), null);

        $this->addToAssertionCount(1);
    }

    public function testReadOnlyTokenMayUseTheProtocolAndUnknownTools(): void
    {
        $guard = $this->guard();
        $guard->assertAllowed(json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']), ['mcp:read']);
        $guard->assertAllowed($this->call('unknown_tool'), ['mcp:read']);
        $guard->assertAllowed('', ['mcp:read']);

        $this->addToAssertionCount(3);
    }

    public function testReadOnlyTokenIsRefusedABodyThatCannotBeRead(): void
    {
        $guard = $this->guard();

        foreach (['not json', '"a string"', '42'] as $body) {
            try {
                $guard->assertAllowed($body, ['mcp:read']);
                self::fail(sprintf('The body %s was not refused.', $body));
            } catch (InsufficientMcpScopeException $exception) {
                self::assertStringContainsString('mcp:write', $exception->getMessage());
            }
        }
    }

    public function testUnreadableBodyIsLeftAloneWhenTheTokenIsNotLimited(): void
    {
        $guard = $this->guard();
        $guard->assertAllowed('not json', []);
        $guard->assertAllowed('not json', null);
        $guard->assertAllowed('not json', ['mcp:read', 'mcp:write']);

        $this->addToAssertionCount(3);
    }

    public function testWriteToolInsideABatchIsRefused(): void
    {
        $batch = json_encode([
            ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'read_tool']],
            ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/call', 'params' => ['name' => 'write_tool']],
        ]);

        $this->expectException(InsufficientMcpScopeException::class);

        $this->guard()->assertAllowed($batch, ['mcp:read']);
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

    private function guard(): McpToolCallScopeGuard
    {
        $meta = static fn (?array $annotations): array => [
            'class' => stdClass::class, 'method' => 'run', 'title' => null,
            'description' => '', 'annotations' => $annotations, 'outputSchema' => null,
        ];

        return new McpToolCallScopeGuard(new McpToolRegistry([
            'read_tool' => $meta(['readOnlyHint' => true]),
            'write_tool' => $meta(['readOnlyHint' => false]),
            'bare_tool' => $meta(null),
        ]));
    }
}
