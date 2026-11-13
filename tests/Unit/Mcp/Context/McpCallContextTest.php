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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Context;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContext;
use RuntimeException;

final class McpCallContextTest extends Unit
{
    public function testIsNotInAnMcpCallByDefault(): void
    {
        $this->assertFalse((new McpCallContext())->isInMcpCall());
    }

    public function testMarksTheCallbackAndReturnsItsResult(): void
    {
        $context = new McpCallContext();

        $result = $context->run(fn (): array => [$context->isInMcpCall()]);

        $this->assertSame([true], $result);
        $this->assertFalse($context->isInMcpCall());
    }

    public function testNestedRunKeepsTheMarkUntilTheOuterCallEnds(): void
    {
        $context = new McpCallContext();
        $inside = [];

        $context->run(function () use ($context, &$inside): void {
            $context->run(static fn () => null);
            $inside[] = $context->isInMcpCall();
        });

        $this->assertSame([true], $inside);
        $this->assertFalse($context->isInMcpCall());
    }

    public function testMarkIsRemovedWhenTheCallbackThrows(): void
    {
        $context = new McpCallContext();

        try {
            $context->run(static function (): void {
                throw new RuntimeException('boom');
            });
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertFalse($context->isInMcpCall());
    }
}
