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
use Pimcore\Bundle\StudioBackendBundle\Notification\Dispatch\Type\NotificationTypeProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestChannel;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Notification\Dispatch\Fixture\TestNotificationTypeProvider;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * The pass is the only thing that tags contributed type providers and channels — the interface
 * attribute does not, in Pimcore's container — so it is the framework's whole extensibility
 * mechanism and worth pinning.
 */
final class NotificationDispatchPassTest extends Unit
{
    public function testTagsProviderAndChannelImplementers(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('a.provider', new Definition(TestNotificationTypeProvider::class));
        $container->setDefinition('a.channel', new Definition(TestChannel::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertTrue(
            $container->getDefinition('a.provider')->hasTag(NotificationTypeProviderInterface::TAG)
        );
        $this->assertTrue(
            $container->getDefinition('a.channel')->hasTag(ChannelInterface::TAG)
        );
    }

    public function testLeavesUnrelatedServicesAlone(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('unrelated', new Definition(stdClass::class));

        (new NotificationDispatchPass())->process($container);

        $this->assertSame([], $container->getDefinition('unrelated')->getTags());
    }

    public function testDoesNotDoubleTagAnAlreadyTaggedService(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TestNotificationTypeProvider::class);
        $definition->addTag(NotificationTypeProviderInterface::TAG);
        $container->setDefinition('a.provider', $definition);

        (new NotificationDispatchPass())->process($container);

        $this->assertCount(1, $container->getDefinition('a.provider')->getTag(NotificationTypeProviderInterface::TAG));
    }

    public function testSkipsAbstractDefinitions(): void
    {
        $container = new ContainerBuilder();
        $definition = new Definition(TestNotificationTypeProvider::class);
        $definition->setAbstract(true);
        $container->setDefinition('abstract.provider', $definition);

        (new NotificationDispatchPass())->process($container);

        $this->assertFalse(
            $container->getDefinition('abstract.provider')->hasTag(NotificationTypeProviderInterface::TAG)
        );
    }
}
