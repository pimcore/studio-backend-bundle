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

use Pimcore\Bundle\StudioBackendBundle\Resolver\Widget\WorkflowPendingItemsResolverDecorator;
use Pimcore\Bundle\StudioDashboardsBundle\Resolver\Widget\WorkflowPendingItemsResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @internal
 */
final class WorkflowPendingItemsResolverCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // Replace the vendor resolver with our enhanced implementation
        $vendorServiceId = WorkflowPendingItemsResolver::class;
        
        if (!$container->hasDefinition($vendorServiceId)) {
            return;
        }

        // Get the original service definition to extract constructor arguments
        $originalDef = $container->getDefinition($vendorServiceId);

        // Create our enhanced resolver with the same dependencies
        $enhancedDef = new Definition(WorkflowPendingItemsResolverDecorator::class);
        $enhancedDef->setPublic(false);
        $enhancedDef->setAutowired(true);
        $enhancedDef->setAutoconfigured(true);

        // Copy the tags from the original service
        foreach ($originalDef->getTags() as $tag => $attributes) {
            $enhancedDef->addTag($tag, $attributes[0] ?? []);
        }

        // Set the enhanced definition
        $container->setDefinition($vendorServiceId, $enhancedDef);
    }
}
