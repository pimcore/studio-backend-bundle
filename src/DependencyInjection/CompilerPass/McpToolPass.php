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
use Mcp\Capability\Discovery\DocBlockParser;
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

        $docBlockParser = new DocBlockParser();

        foreach ($container->findTaggedServiceIds(McpToolRegistry::TAG) as $serviceId => $tags) {
            // The class, not the service id: the registry hands this value to ReflectionMethod
            // and to the SDK as the handler class, and a service id need not be its class.
            $class = $this->resolveClass($container, $serviceId);
            $tools = $this->extractToolMetadata($class, $docBlockParser);
            if ($tools === []) {
                throw new InvalidArgumentException(sprintf(
                    'Service "%s" is tagged "%s" but exposes no #[McpTool] method.',
                    $serviceId,
                    McpToolRegistry::TAG
                ));
            }

            // Keyed by class, so the SDK's container lookup for the handler class finds the
            // service whatever its id is.
            $locatorRefs[$class] = new Reference($serviceId);

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
                    'class' => $class,
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
     * @return list<array{
     *     name: string,
     *     method: string,
     *     title: string|null,
     *     description: string,
     *     annotations: array<string, mixed>|null,
     *     outputSchema: array<string, mixed>|null
     * }>
     */
    private function extractToolMetadata(string $class, DocBlockParser $docBlockParser): array
    {
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
                // Without a description on the attribute the DocBlock supplies it, as the SDK
                // does for the tools it discovers itself. An empty string here would suppress
                // that fallback, since the SDK only consults the DocBlock for a null one.
                'description' => $attribute->description
                    ?? $docBlockParser->getDescription($docBlockParser->parseDocBlock($method->getDocComment()))
                    ?? '',
                'annotations' => $attribute->annotations?->jsonSerialize(),
                'outputSchema' => $attribute->outputSchema,
            ];
        }

        return $tools;
    }

    /**
     * The service's class, with any `%parameter%` in it resolved, or the id itself when the
     * definition names no class (an id that is the class, the usual case).
     */
    private function resolveClass(ContainerBuilder $container, string $serviceId): string
    {
        $class = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

        return (string) $container->getParameterBag()->resolveValue($class);
    }
}
