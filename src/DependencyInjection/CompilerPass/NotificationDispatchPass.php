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

use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Tags every notification type descriptor and delivery channel so the registries collect them.
 * A compiler pass rather than #[AutoconfigureTag] alone because the interface attribute does not
 * tag implementers in Pimcore's container; type ids are validated by NotificationTypeRegistry.
 *
 * @internal
 */
final readonly class NotificationDispatchPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $this->concreteClass($container, $definition);
            if ($class === null) {
                continue;
            }

            if ($class->implementsInterface(NotificationTypeDescriptorInterface::class)) {
                $this->tag($definition, NotificationTypeDescriptorInterface::TAG);
            }

            if ($class->implementsInterface(ChannelInterface::class)) {
                $this->tag($definition, ChannelInterface::TAG);
            }
        }
    }

    /**
     * The concrete class a definition instantiates, or null when there is nothing to classify.
     * getReflectionClass() resolves parameters and contains the fatal a missing parent would raise.
     *
     * @return ReflectionClass<object>|null
     */
    private function concreteClass(ContainerBuilder $container, Definition $definition): ?ReflectionClass
    {
        if ($definition->isAbstract() || $definition->isSynthetic()) {
            return null;
        }

        $class = $container->getReflectionClass($definition->getClass(), false);

        return $class === null || $class->isAbstract() ? null : $class;
    }

    private function tag(Definition $definition, string $tag): void
    {
        if (!$definition->hasTag($tag)) {
            $definition->addTag($tag);
        }
    }
}
