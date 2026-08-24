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
 * A tool's native text result. Kept transport-agnostic so tools do not depend on
 * the MCP SDK; the per-server endpoint maps this onto the wire result type.
 *
 * @internal
 */
final readonly class McpToolResult
{
    private function __construct(
        public string $text,
        public bool $isError,
    ) {
    }

    public static function text(string $text): self
    {
        return new self($text, false);
    }

    public static function error(string $text): self
    {
        return new self($text, true);
    }
}
