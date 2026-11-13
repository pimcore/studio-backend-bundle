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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Server;

use Mcp\Capability\Registry\ElementReference;
use Mcp\Capability\Registry\ReferenceHandlerInterface;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContextInterface;
use Pimcore\Model\Version\CoauthorContextInterface;

/**
 * The one stamping point of a configured MCP server: decorates the SDK's reference handler so that every
 * tool call runs marked as an MCP call and with the version coauthor `mcp-server` plus the server's URL slug.
 * Tools never set a coauthor themselves, and the client's own name is never used.
 *
 * @internal
 */
final readonly class CoauthorReferenceHandler implements ReferenceHandlerInterface
{
    public const string COAUTHOR_TYPE = 'mcp-server';

    public function __construct(
        private ReferenceHandlerInterface $inner,
        private CoauthorContextInterface $coauthorContext,
        private McpCallContextInterface $mcpCallContext,
        private string $serverSlug,
    ) {
    }

    /**
     * @param array<string, mixed> $arguments
     */
    public function handle(ElementReference $reference, array $arguments): mixed
    {
        return $this->mcpCallContext->run(
            fn (): mixed => $this->coauthorContext->withCoauthor(
                self::COAUTHOR_TYPE,
                $this->serverSlug,
                fn (): mixed => $this->inner->handle($reference, $arguments),
            )
        );
    }
}
