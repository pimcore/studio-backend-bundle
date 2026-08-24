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

use Pimcore\Bundle\StudioBackendBundle\Mcp\McpScopes;

/**
 * The client-facing description of a tool plus what the server needs to expose
 * it: identity, MCP annotations, and the JSON schemas. The {@see $name} is the
 * tool's stable identifier (referenced from a server's tool list).
 *
 * @internal
 */
final readonly class McpToolDefinition
{
    /**
     * @param array<string, mixed>      $inputSchema  JSON Schema for the arguments
     * @param array<string, mixed>|null $outputSchema JSON Schema for the result, when structured
     */
    public function __construct(
        public string $name,
        public string $title,
        public string $description,
        public McpToolAnnotations $annotations,
        public array $inputSchema = ['type' => 'object'],
        public ?array $outputSchema = null,
    ) {
    }

    /**
     * The OAuth scope a caller must hold to invoke this tool. Read-only tools need
     * {@see McpScopes::READ}; everything else (the fail-safe default) needs
     * {@see McpScopes::WRITE}.
     */
    public function requiredScope(): string
    {
        return $this->annotations->readOnly ? McpScopes::READ : McpScopes::WRITE;
    }
}
