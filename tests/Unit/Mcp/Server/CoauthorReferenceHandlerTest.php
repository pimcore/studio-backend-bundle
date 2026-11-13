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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mcp\Server;

use Codeception\Test\Unit;
use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ReferenceHandlerInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContext;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Server\CoauthorReferenceHandler;
use Pimcore\Model\Version\CoauthorContext;
use RuntimeException;

final class CoauthorReferenceHandlerTest extends Unit
{
    private CoauthorContext $coauthorContext;

    private McpCallContext $mcpCallContext;

    protected function _before(): void
    {
        $this->coauthorContext = new CoauthorContext();
        $this->mcpCallContext = new McpCallContext();
    }

    public function testToolCallRunsWithTheServerCoauthorAndIsMarkedAsMcpCall(): void
    {
        $seen = [];
        $handler = $this->handler('pme-test', function () use (&$seen): string {
            $seen = [
                $this->coauthorContext->getType(),
                $this->coauthorContext->getCoauthor(),
                $this->mcpCallContext->isInMcpCall(),
            ];

            return 'result';
        });

        $result = $handler->handle(new ElementReference(['Some\\Tool', 'run']), ['a' => 1]);

        $this->assertSame('result', $result);
        $this->assertSame(['mcp-server', 'pme-test', true], $seen);
    }

    public function testCoauthorAndMarkAreClearedAfterTheCall(): void
    {
        $this->handler('pme-test', static fn (): string => 'x')->handle(new ElementReference(['T', 'm']), []);

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertNull($this->coauthorContext->getType());
        $this->assertFalse($this->mcpCallContext->isInMcpCall());
    }

    public function testCoauthorAndMarkAreClearedWhenTheToolThrows(): void
    {
        $handler = $this->handler('pme-test', static function (): never {
            throw new RuntimeException('boom');
        });

        try {
            $handler->handle(new ElementReference(['T', 'm']), []);
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException) {
            // expected
        }

        $this->assertFalse($this->coauthorContext->isActive());
        $this->assertFalse($this->mcpCallContext->isInMcpCall());
    }

    public function testTheClientsOwnNameIsNeverStamped(): void
    {
        $seen = [];
        $handler = $this->handler('pme-test', function () use (&$seen): null {
            $seen = [$this->coauthorContext->getType(), $this->coauthorContext->getCoauthor()];

            return null;
        });

        // the SDK passes the session (with the clientInfo the client claimed) in the arguments
        $handler->handle(new ElementReference(['T', 'm']), ['_session' => ['client_info' => ['name' => 'evil-client']]]);

        $this->assertSame(['mcp-server', 'pme-test'], $seen);
    }

    public function testSwitchedOffCoauthorContextStaysUnstamped(): void
    {
        $this->coauthorContext->disable();
        $active = [];
        $handler = $this->handler('pme-test', function () use (&$active): null {
            $active[] = $this->coauthorContext->isActive();

            return null;
        });

        $handler->handle(new ElementReference(['T', 'm']), []);

        $this->assertSame([false], $active);
    }

    private function handler(string $slug, callable $tool): CoauthorReferenceHandler
    {
        $inner = new class($tool) implements ReferenceHandlerInterface {
            /**
             * @param callable(): mixed $tool
             */
            public function __construct(private $tool)
            {
            }

            public function handle(ElementReference $reference, array $arguments): mixed
            {
                return ($this->tool)();
            }
        };

        return new CoauthorReferenceHandler($inner, $this->coauthorContext, $this->mcpCallContext, $slug);
    }
}
