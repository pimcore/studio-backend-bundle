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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\McpToolPass;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection\Fixture\CustomServiceIdMcpTool;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection\Fixture\DocBlockDescribedMcpTool;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class McpToolPassTest extends Unit
{
    /**
     * A service id need not be the class name. The registry hands the stored value to
     * ReflectionMethod and to the SDK as the handler class, so it has to be the class, and
     * the locator has to resolve that class to the service.
     */
    public function testAToolUnderACustomServiceIdIsRegisteredByItsClass(): void
    {
        $container = $this->process(['app.custom_tool' => CustomServiceIdMcpTool::class]);

        $metadata = $container->getDefinition(McpToolRegistry::class)->getArgument('$toolMetadata');
        $this->assertSame(CustomServiceIdMcpTool::class, $metadata['custom_id_tool']['class']);

        $locatorRefs = $container->getDefinition('pimcore_studio_backend.mcp.tool_locator')->getArgument(0);
        $this->assertArrayHasKey(CustomServiceIdMcpTool::class, $locatorRefs);
        $this->assertEquals(new Reference('app.custom_tool'), $locatorRefs[CustomServiceIdMcpTool::class]);
    }

    public function testAnExplicitDescriptionIsKept(): void
    {
        $metadata = $this->metadata([CustomServiceIdMcpTool::class => CustomServiceIdMcpTool::class]);

        $this->assertSame('Explicit description.', $metadata['custom_id_tool']['description']);
    }

    /**
     * Without a description on the attribute the DocBlock supplies it, as the SDK does for
     * the tools it discovers itself: passing an empty string would suppress that fallback.
     */
    public function testAnOmittedDescriptionFallsBackToTheDocBlock(): void
    {
        $metadata = $this->metadata([DocBlockDescribedMcpTool::class => DocBlockDescribedMcpTool::class]);

        $this->assertSame(
            "Returns the greeting.\n\nLonger explanation of what the greeting is for.",
            $metadata['doc_tool']['description'],
        );
        $this->assertSame('', $metadata['undescribed_tool']['description']);
    }

    /**
     * @param array<string, class-string> $services service id => class
     *
     * @return array<string, array<string, mixed>>
     */
    private function metadata(array $services): array
    {
        return $this->process($services)->getDefinition(McpToolRegistry::class)->getArgument('$toolMetadata');
    }

    /**
     * @param array<string, class-string> $services service id => class
     */
    private function process(array $services): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setDefinition(McpToolRegistry::class, new Definition(McpToolRegistry::class));

        foreach ($services as $id => $class) {
            $container->setDefinition($id, (new Definition($class))->addTag(McpToolRegistry::TAG));
        }

        (new McpToolPass())->process($container);

        return $container;
    }
}
