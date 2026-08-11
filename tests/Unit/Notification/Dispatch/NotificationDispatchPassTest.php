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
use Symfony\Component\DependencyInjection\Reference;

/**
 * The pass is the only thing that tags contributed descriptors and channels — the interface
 * attribute does not, in Pimcore's container — so it is the framework's whole extensibility
 * mechanism and worth pinning. It also gates the transport channels on whether any type can be
 * delivered externally, so those cases are pinned here too.
 */
final class NotificationDispatchPassTest extends Unit
{
    public function testTagsDescriptorAndChannelImplementers(): void
    {
        $container = new ContainerBuilder();
        // The descriptor allows external delivery, so the channel is a live consumer and stays.
        $container->setDefinition('a.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments(['a.type', true]);
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertTrue(
            $container->getDefinition('a.descriptor')->hasTag(NotificationTypeDescriptorInterface::TAG)
        );
        $this->assertTrue(
            $container->getDefinition('a.channel')->hasTag(ChannelInterface::TAG)
        );
    }

    /**
     * The whole point of the gate: with only types that never allow external delivery (a core-only
     * install has just the 'info' catch-all), a transport channel is dead weight and is removed.
     */
    public function testRemovesTransportChannelsWhenNoTypeAllowsExternalDelivery(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('a.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments(['a.type', false]);
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertFalse($container->hasDefinition('a.channel'));
        // The descriptor itself is still contributed — only the channel is gated.
        $this->assertTrue(
            $container->getDefinition('a.descriptor')->hasTag(NotificationTypeDescriptorInterface::TAG)
        );
    }

    /**
     * A single externally-deliverable type anywhere is enough to bring the channels back, even
     * alongside types that do not allow it.
     */
    public function testKeepsTransportChannelsWhenAnyTypeAllowsExternalDelivery(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('internal.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments(['internal.type', false]);
        $container->setDefinition('external.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments(['external.type', true]);
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertTrue($container->hasDefinition('a.channel'));
        $this->assertTrue(
            $container->getDefinition('a.channel')->hasTag(ChannelInterface::TAG)
        );
    }

    /**
     * A descriptor that cannot be materialised at compile time (service-reference arguments) must
     * not be read as "no external delivery" — that would silently strip a channel a bundle wanted.
     * It is assumed external-capable, so the channel survives.
     */
    public function testKeepsChannelsWhenADescriptorCannotBeEvaluatedAtCompileTime(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('opaque.descriptor', new Definition(TestNotificationTypeDescriptor::class))
            ->setArguments([new Reference('some.service')]);
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertTrue($container->hasDefinition('a.channel'));
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
