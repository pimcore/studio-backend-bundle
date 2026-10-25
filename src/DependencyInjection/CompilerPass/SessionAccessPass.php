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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpFoundation\RequestMatcher\PathRequestMatcher;
use function sprintf;

/**
 * Opens the session status route (`<url_prefix>/session`) to requests without a session.
 *
 * A project's `security.access_control` ends in a rule that requires a Studio user for the whole prefix, and
 * Symfony does not let a bundle add a rule of its own to that list. The rule goes straight onto the access map
 * instead, in front of the project's rules, so projects need no change of their security.yaml. The route
 * itself answers 200 either way.
 *
 * @internal
 */
final class SessionAccessPass implements CompilerPassInterface
{
    private const string ACCESS_MAP = 'security.access_map';

    private const string URL_PREFIX = 'pimcore_studio_backend.url_prefix';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::ACCESS_MAP) || !$container->hasParameter(self::URL_PREFIX)) {
            return;
        }

        $matcher = new Definition(PathRequestMatcher::class, [
            sprintf('^%s/session$', $container->getParameter(self::URL_PREFIX)),
        ]);

        $accessMap = $container->getDefinition(self::ACCESS_MAP);
        $accessMap->setMethodCalls([
            ['add', [$matcher, ['PUBLIC_ACCESS'], null]],
            ...$accessMap->getMethodCalls(),
        ]);
    }
}
