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

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass;

use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Tool\McpToolInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function array_keys;

/**
 * Guards the MCP tool tag: any service carrying it must implement
 * {@see McpToolInterface}. Interface implementations are auto-tagged, so this
 * only catches a hand-written tag on the wrong service.
 *
 * @internal
 */
final class McpToolPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        foreach (array_keys($container->findTaggedServiceIds(McpToolRegistry::TAG)) as $serviceId) {
            $this->checkInterface($serviceId, McpToolInterface::class);
        }
    }
}
