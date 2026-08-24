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

use Pimcore\Bundle\StudioBackendBundle\Mcp\Exception\DuplicateMcpToolException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use function array_keys;
use function array_values;

/**
 * Collects every service tagged {@see TAG} (auto-applied to {@see McpToolInterface}
 * implementations) into a name-keyed catalogue.
 *
 * @internal
 */
final class McpToolRegistry implements McpToolRegistryInterface
{
    public const string TAG = 'pimcore.studio_backend.mcp_tool';

    /**
     * @var array<string, McpToolInterface>
     */
    private array $tools = [];

    /**
     * @param iterable<McpToolInterface> $tools
     *
     * @throws DuplicateMcpToolException
     */
    public function __construct(
        #[AutowireIterator(self::TAG)]
        iterable $tools = [],
    ) {
        foreach ($tools as $tool) {
            $name = $tool->getDefinition()->name;
            if (isset($this->tools[$name])) {
                throw new DuplicateMcpToolException($name);
            }

            $this->tools[$name] = $tool;
        }
    }

    public function all(): array
    {
        return array_values($this->tools);
    }

    public function has(string $name): bool
    {
        return isset($this->tools[$name]);
    }

    public function get(string $name): ?McpToolInterface
    {
        return $this->tools[$name] ?? null;
    }

    public function names(): array
    {
        return array_keys($this->tools);
    }
}
