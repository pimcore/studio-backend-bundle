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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\NotificationDispatchPass;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Channel\ChannelInterface;
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Descriptor\NotificationTypeDescriptorInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeDescriptor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * The pass is the only thing that tags contributed descriptors and channels — the interface
 * attribute does not, in Pimcore's container — so it is the framework's whole extensibility
 * mechanism and worth pinning.
 */
final class NotificationDispatchPassTest extends Unit
{
    public function testTagsDescriptorAndChannelImplementers(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('a.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments(['a.type']);
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertTrue(
            $container->getDefinition('a.descriptor')->hasTag(NotificationTypeDescriptorInterface::TAG)
        );
        $this->assertTrue(
            $container->getDefinition('a.channel')->hasTag(ChannelInterface::TAG)
        );
    }

    public function testLeavesUnrelatedServicesAlone(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('unrelated', new Definition(\stdClass::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertSame([], $container->getDefinition('unrelated')->getTags());
    }

    /**
     * A bundle that already tags its service explicitly must not end up tagged twice.
     */
    public function testDoesNotDoubleTagAnAlreadyTaggedService(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TestNotificationTypeDescriptor::class);
        $definition->setArguments(['a.type']);
        $definition->addTag(NotificationTypeDescriptorInterface::TAG);
        $container->setDefinition('a.descriptor', $definition);

        (new NotificationDispatchPass())->process($container);

        $this->assertCount(1, $container->getDefinition('a.descriptor')->getTag(NotificationTypeDescriptorInterface::TAG));
    }

    public function testSkipsAbstractDefinitions(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TestNotificationTypeDescriptor::class);
        $definition->setAbstract(true);
        $container->setDefinition('abstract.descriptor', $definition);

        (new NotificationDispatchPass())->process($container);

        $this->assertFalse(
            $container->getDefinition('abstract.descriptor')->hasTag(NotificationTypeDescriptorInterface::TAG)
        );
    }
}
