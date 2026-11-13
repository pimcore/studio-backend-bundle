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

namespace Pimcore\Bundle\StudioBackendBundle\Mcp\Context;

use Symfony\Contracts\Service\ResetInterface;

/**
 * @internal
 */
final class McpCallContext implements McpCallContextInterface, ResetInterface
{
    private int $depth = 0;

    public function isInMcpCall(): bool
    {
        return $this->depth > 0;
    }

    public function run(callable $callback): mixed
    {
        $this->depth++;

        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }

    public function reset(): void
    {
        $this->depth = 0;
    }
}
