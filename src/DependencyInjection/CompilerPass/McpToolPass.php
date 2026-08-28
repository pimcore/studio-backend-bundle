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

use InvalidArgumentException;
use Mcp\Capability\Attribute\McpTool;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Registry\McpToolRegistry;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function sprintf;

/**
 * Collects services tagged {@see McpToolRegistry::TAG} into the tool registry.
 * Each tagged service must expose at least one SDK-native `#[McpTool]` method; the
 * pass reflects the attribute into plain-array descriptors (so they survive
 * container compilation) and builds a service locator the SDK uses to resolve the
 * backing service at call time. Tool names must be unique across all tools.
 *
 * @internal
 */
final class McpToolPass implements CompilerPassInterface
{
    private const string LOCATOR_ID = 'pimcore_studio_backend.mcp.tool_locator';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(McpToolRegistry::class)) {
            return;
        }

        $metadata = [];
        $locatorRefs = [];

        foreach ($container->findTaggedServiceIds(McpToolRegistry::TAG) as $serviceId => $tags) {
            $tools = $this->extractToolMetadata($container, $serviceId);
            if ($tools === []) {
                throw new InvalidArgumentException(sprintf(
                    'Service "%s" is tagged "%s" but exposes no #[McpTool] method.',
                    $serviceId,
                    McpToolRegistry::TAG
                ));
            }

            $locatorRefs[$serviceId] = new Reference($serviceId);

            foreach ($tools as $tool) {
                if (isset($metadata[$tool['name']])) {
                    throw new InvalidArgumentException(sprintf(
                        'Duplicate MCP tool name "%s": already provided by "%s", conflict with "%s".',
                        $tool['name'],
                        $metadata[$tool['name']]['class'],
                        $serviceId
                    ));
                }

                $metadata[$tool['name']] = [
                    'class' => $serviceId,
                    'method' => $tool['method'],
                    'title' => $tool['title'],
                    'description' => $tool['description'],
                    'annotations' => $tool['annotations'],
                    'outputSchema' => $tool['outputSchema'],
                ];
            }
        }

        $locator = new Definition(ServiceLocator::class, [$locatorRefs]);
        $locator->addTag('container.service_locator');
        $container->setDefinition(self::LOCATOR_ID, $locator);

        $registry = $container->getDefinition(McpToolRegistry::class);
        $registry->setArgument('$toolMetadata', $metadata);
        $registry->setArgument('$toolLocator', new Reference(self::LOCATOR_ID));
    }

    /**
     * @return list<array{name: string, method: string, title: string|null, description: string, annotations: array<string, mixed>|null, outputSchema: array<string, mixed>|null}>
     */
    private function extractToolMetadata(ContainerBuilder $container, string $serviceId): array
    {
        $class = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

        try {
            $reflection = new ReflectionClass($class);
        } catch (ReflectionException) {
            return [];
        }

        $tools = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(McpTool::class);
            if ($attributes === []) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            $tools[] = [
                'name' => $attribute->name ?? $method->getName(),
                'method' => $method->getName(),
                'title' => $attribute->title,
                'description' => $attribute->description ?? '',
                'annotations' => $attribute->annotations?->jsonSerialize(),
                'outputSchema' => $attribute->outputSchema,
            ];
        }

        return $tools;
    }
}
