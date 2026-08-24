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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Tool;

/**
 * The MCP behaviour hints a tool declares about itself, mirroring the MCP
 * ToolAnnotations set (readOnly / destructive / idempotent / openWorld). Authored
 * server-side and therefore trustworthy here: {@see McpToolDefinition::requiredScope()}
 * derives the required OAuth scope from {@see $readOnly}. The default (all false)
 * is the fail-safe: an unannotated tool is treated as a write.
 *
 * @internal
 */
final readonly class McpToolAnnotations
{
    public function __construct(
        public bool $readOnly = false,
        public bool $destructive = false,
        public bool $idempotent = false,
        public bool $openWorld = false,
    ) {
    }

    /**
     * @return array<string, bool> the MCP-wire annotation hint map
     */
    public function toArray(): array
    {
        return [
            'readOnlyHint' => $this->readOnly,
            'destructiveHint' => $this->destructive,
            'idempotentHint' => $this->idempotent,
            'openWorldHint' => $this->openWorld,
        ];
    }
}
