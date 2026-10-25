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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass\SessionAccessPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use Symfony\Component\Security\Http\AccessMap;

/**
 * A project's access_control requires a Studio user for the whole url prefix and cannot be extended by a
 * bundle, so the session status route is put onto the access map itself, in front of the project's rules.
 *
 * @internal
 */
final class SessionAccessPassTest extends Unit
{
    public function testSessionRouteIsPublicAndComesBeforeTheProjectsRules(): void
    {
        $accessMap = $this->process('/pimcore-studio/api');

        $this->assertSame(['PUBLIC_ACCESS'], $this->attributesFor($accessMap, '/pimcore-studio/api/session'));
        $this->assertSame(
            ['ROLE_PIMCORE_USER'],
            $this->attributesFor($accessMap, '/pimcore-studio/api/user/current-user-information')
        );
        $this->assertSame(
            ['ROLE_PIMCORE_USER'],
            $this->attributesFor($accessMap, '/pimcore-studio/api/session/other')
        );
        $this->assertSame(['ROLE_PIMCORE_USER'], $this->attributesFor($accessMap, '/pimcore-studio/api/session%0A'));
    }

    public function testSessionRouteFollowsACustomPrefix(): void
    {
        $accessMap = $this->process('/my-studio/api');

        $this->assertSame(['PUBLIC_ACCESS'], $this->attributesFor($accessMap, '/my-studio/api/session'));
        $this->assertSame(['ROLE_PIMCORE_USER'], $this->attributesFor($accessMap, '/pimcore-studio/api/session'));
    }

    public function testDoesNothingWithoutSecurity(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore_studio_backend.url_prefix', '/pimcore-studio/api');

        (new SessionAccessPass())->process($container);

        $this->assertFalse($container->hasDefinition('security.access_map'));
    }

    private function process(string $prefix): AccessMap
    {
        $container = new ContainerBuilder();
        $container->setParameter('pimcore_studio_backend.url_prefix', $prefix);

        // what SecurityExtension builds from the project's access_control
        $projectRule = new Definition(PathRequestMatcher::class, ['^/pimcore-studio/api']);
        $container->setDefinition('security.access_map', new Definition(AccessMap::class))
            ->addMethodCall('add', [$projectRule, ['ROLE_PIMCORE_USER'], null]);

        (new SessionAccessPass())->process($container);

        $accessMap = new AccessMap();
        foreach ($container->getDefinition('security.access_map')->getMethodCalls() as [$method, $arguments]) {
            $this->assertSame('add', $method);
            [$matcher, $attributes, $channel] = $arguments;
            $accessMap->add(new PathRequestMatcher($matcher->getArgument(0)), $attributes, $channel);
        }

        return $accessMap;
    }

    /**
     * @return list<string>
     */
    private function attributesFor(AccessMap $accessMap, string $path): array
    {
        return $accessMap->getPatterns(Request::create($path))[0] ?? [];
    }
}
