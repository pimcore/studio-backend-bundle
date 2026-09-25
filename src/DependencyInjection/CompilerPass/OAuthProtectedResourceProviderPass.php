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

use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\ProtectedResourceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function array_keys;
use function class_exists;

/**
 * Fails the build when a service tagged as a protected-resource provider does not
 * implement {@see ProtectedResourceProviderInterface}.
 *
 * The tag is consumed as an untyped `!tagged_iterator`, so without this a mistagged
 * service compiles cleanly and only breaks when something first reads the registry -
 * and it breaks quietly: the resource is absent, its RFC 9728 document 404s, its scopes
 * disappear from the catalogue, and a client asking for that audience is refused with
 * `invalid_request`. None of that names the real cause.
 *
 * @internal
 */
final class OAuthProtectedResourceProviderPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = array_keys(
            $container->findTaggedServiceIds(ProtectedResourceProviderInterface::TAG),
        );

        foreach ($taggedServices as $serviceId) {
            // The definition's class rather than the service id. They coincide for the
            // FQCN-keyed services this bundle registers, but a provider registered under
            // a name would otherwise reach class_implements() as a non-class and raise a
            // PHP warning on the way to the right exception.
            $class = $container->getDefinition($serviceId)->getClass() ?? $serviceId;

            // class_exists() first, for its autoloading side effect: checkInterface() asks
            // class_implements() with autoloading disabled, so a provider whose class the
            // compiler has not happened to load yet would look like it implements nothing
            // and be rejected although it is correct. A class that genuinely does not
            // exist is left to the container, which reports that far better than this can.
            if (!class_exists($class)) {
                continue;
            }

            $this->checkInterface($class, ProtectedResourceProviderInterface::class);
        }
    }
}
