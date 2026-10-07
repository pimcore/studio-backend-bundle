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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\OAuthProtectedResourceProviderPass;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\ProtectedResourceProvider;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * The tag is consumed as an untyped `!tagged_iterator`, so nothing else checks what is in
 * it. Without this pass a mistagged service compiles and then fails silently: the resource
 * is simply absent, its metadata document 404s, and its scopes leave the catalogue, none
 * of which names the cause.
 *
 * @internal
 */
final class OAuthProtectedResourceProviderPassTest extends Unit
{
    public function testAcceptsAProviderThatImplementsTheInterface(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            ProtectedResourceProvider::class,
            $this->taggedDefinition(ProtectedResourceProvider::class),
        );

        (new OAuthProtectedResourceProviderPass())->process($container);

        $this->assertTrue(
            $container->getDefinition(ProtectedResourceProvider::class)
                ->hasTag(ProtectedResourceProviderInterface::TAG),
        );
    }

    public function testRejectsATaggedServiceThatDoesNotImplementTheInterface(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(stdClass::class, $this->taggedDefinition(stdClass::class));

        $this->expectException(MustImplementInterfaceException::class);
        $this->expectExceptionMessageMatches('/must implement/');

        (new OAuthProtectedResourceProviderPass())->process($container);
    }

    /**
     * A service registered under a name rather than its FQCN still has to be checked
     * against its actual class, not against the id, which is not a class at all.
     */
    public function testResolvesTheClassForANamedService(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('my_bundle.oauth.provider', $this->taggedDefinition(stdClass::class));

        $this->expectException(MustImplementInterfaceException::class);
        $this->expectExceptionMessageMatches('/stdClass must implement/');

        (new OAuthProtectedResourceProviderPass())->process($container);
    }

    /**
     * The guard against a false rejection: the shared trait asks class_implements() with
     * autoloading off, so a provider class the compiler has not loaded yet must still be
     * recognised rather than failing the build for a correct implementation.
     */
    public function testAcceptsAProviderWhoseClassIsNotLoadedYet(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition(
            'lazy.provider',
            $this->taggedDefinition('Pimcore\\Bundle\\StudioBackendBundle\\Mcp\\ProtectedResourceProvider'),
        );

        $this->expectNotToPerformAssertions();

        (new OAuthProtectedResourceProviderPass())->process($container);
    }

    public function testIgnoresUntaggedServices(): void
    {
        $container = new ContainerBuilder();
        $container->setDefinition('unrelated', new Definition(stdClass::class));

        (new OAuthProtectedResourceProviderPass())->process($container);

        $this->assertSame([], $container->getDefinition('unrelated')->getTags());
    }

    public function testEmptyContainerIsFine(): void
    {
        $this->expectNotToPerformAssertions();

        (new OAuthProtectedResourceProviderPass())->process(new ContainerBuilder());
    }

    private function taggedDefinition(string $class): Definition
    {
        return (new Definition($class))->addTag(ProtectedResourceProviderInterface::TAG);
    }
}
