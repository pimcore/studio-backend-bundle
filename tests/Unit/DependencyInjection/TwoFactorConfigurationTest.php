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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\PimcoreStudioBackendExtension;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\UserCondition;
use Scheb\TwoFactorBundle\DependencyInjection\SchebTwoFactorExtension;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Studio configures scheb itself: nothing else in a Studio installation enables the Google
 * authenticator or sets the issuer and server name shown in the authenticator app.
 *
 * @internal
 */
final class TwoFactorConfigurationTest extends Unit
{
    public function testSchebIsConfiguredFromStudioConfig(): void
    {
        $scheb = $this->prependWith([
            'two_factor_authentication' => ['issuer' => 'Acme PIM', 'server_name' => 'pim.acme.test'],
        ]);

        $this->assertTrue($scheb['google']['enabled']);
        $this->assertSame('Acme PIM', $scheb['google']['issuer']);
        $this->assertSame('pim.acme.test', $scheb['google']['server_name']);
        $this->assertSame(UserCondition::class, $scheb['two_factor_condition']);
        $this->assertArrayNotHasKey('security_tokens', $scheb);
    }

    public function testServerNameDefaultsToTheRouterHost(): void
    {
        $scheb = $this->prependWith([]);

        $this->assertSame('Pimcore', $scheb['google']['issuer']);
        $this->assertSame('%router.request_context.host%', $scheb['google']['server_name']);
    }

    /**
     * Config for an unregistered extension fails the container compile.
     */
    public function testNothingIsPrependedWhenSchebIsNotRegistered(): void
    {
        $container = $this->createContainer(withScheb: false);

        (new PimcoreStudioBackendExtension())->prepend($container);

        $this->assertSame([], $container->getExtensionConfig('scheb_two_factor'));
    }

    /**
     * @param array<string, mixed> $studioConfig
     *
     * @return array<string, mixed>
     */
    private function prependWith(array $studioConfig): array
    {
        $container = $this->createContainer(withScheb: true);
        $container->prependExtensionConfig('pimcore_studio_backend', $studioConfig);

        (new PimcoreStudioBackendExtension())->prepend($container);

        $configs = $container->getExtensionConfig('scheb_two_factor');
        $this->assertCount(1, $configs);

        return $configs[0];
    }

    private function createContainer(bool $withScheb): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new PimcoreStudioBackendExtension());
        if ($withScheb) {
            $container->registerExtension(new SchebTwoFactorExtension());
        }

        return $container;
    }
}
