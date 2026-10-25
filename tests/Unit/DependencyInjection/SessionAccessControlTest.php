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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * An existing project's access_control only knows `^/pimcore-studio/api` as ROLE_PIMCORE_USER, so
 * the bundle itself opens the session status route. The prepended rule has to come first and has to
 * follow a custom url_prefix.
 *
 * @internal
 */
final class SessionAccessControlTest extends Unit
{
    public function testSessionRouteIsPublicForTheDefaultPrefix(): void
    {
        $rules = $this->prependedAccessControl([]);

        $this->assertSame(
            [['path' => '^/pimcore-studio/api/session$', 'roles' => 'PUBLIC_ACCESS']],
            $rules
        );
    }

    public function testSessionRouteFollowsACustomPrefix(): void
    {
        $rules = $this->prependedAccessControl(['url_prefix' => '/my-studio/api/']);

        $this->assertSame('^/my-studio/api/session$', $rules[0]['path']);
    }

    public function testRuleStaysFirstAheadOfTheProjectsOwnRules(): void
    {
        $container = $this->createContainer([]);
        $container->prependExtensionConfig('security', [
            'access_control' => [['path' => '^/pimcore-studio/api', 'roles' => 'ROLE_PIMCORE_USER']],
        ]);
        (new PimcoreStudioBackendExtension())->prepend($container);

        $configs = $container->getExtensionConfig('security');

        // prepend() puts the bundle's config before everything registered earlier
        $this->assertSame('PUBLIC_ACCESS', $configs[0]['access_control'][0]['roles']);
    }

    /**
     * @param array<string, mixed> $studioConfig
     *
     * @return list<array{path: string, roles: string}>
     */
    private function prependedAccessControl(array $studioConfig): array
    {
        $container = $this->createContainer($studioConfig);
        (new PimcoreStudioBackendExtension())->prepend($container);

        return $container->getExtensionConfig('security')[0]['access_control'];
    }

    /**
     * @param array<string, mixed> $studioConfig
     */
    private function createContainer(array $studioConfig): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->registerExtension(new PimcoreStudioBackendExtension());
        $container->registerExtension($this->createExtension('security'));
        $container->registerExtension($this->createExtension('framework'));
        $container->loadFromExtension('pimcore_studio_backend', $studioConfig);

        return $container;
    }

    private function createExtension(string $alias): Extension
    {
        return new class($alias) extends Extension {
            public function __construct(private readonly string $alias)
            {
            }

            public function load(array $configs, ContainerBuilder $container): void
            {
            }

            public function getAlias(): string
            {
                return $this->alias;
            }
        };
    }
}
