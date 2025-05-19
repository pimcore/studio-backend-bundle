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
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Parser\Resolver\ResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\FieldDefinition\Service\Loader\TaggedIteratorResolverLoader;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final readonly class FieldDefinitionResolverPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = array_keys(
            [
                ... $container->findTaggedServiceIds(TaggedIteratorResolverLoader::FIELD_DEFINITION_RESOLVER_TAG),

            ]
        );

        foreach ($taggedServices as $environmentType) {
            $this->checkInterface($environmentType, ResolverInterface::class);
        }
    }
}
