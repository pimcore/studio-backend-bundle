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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Registry;

use Mcp\Schema\ToolAnnotations;

/**
 * A registered MCP tool as the studio backend sees it: the SDK-native metadata
 * from the tool's `#[McpTool]` attribute plus the service class/method that backs
 * it. The tool itself is an SDK-native `#[McpTool]` service (no bundle-specific
 * interface); this is only the collected descriptor the registry hands out.
 *
 * @internal
 */
final readonly class McpToolReference
{
    /**
     * @param class-string      $className    the tagged service id/class providing the tool
     * @param array<string, mixed>|null $outputSchema
     */
    public function __construct(
        public string $name,
        public ?string $title,
        public string $description,
        public ?ToolAnnotations $annotations,
        public ?array $outputSchema,
        public string $className,
        public string $method,
    ) {
    }

    public function isReadOnly(): bool
    {
        return $this->annotations?->readOnlyHint === true;
    }

    public function isDestructive(): bool
    {
        return $this->annotations?->destructiveHint === true;
    }
}
