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
use Pimcore\Bundle\StudioBackendBundle\Listing\Mapper\FilterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class FilterMapperPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $locator = $container->findDefinition('listing.query_to_payload_filter.service_locator');

        if (empty($locator->getArguments())) {
            return;
        }

        // first argument is an array of mappers
        foreach (current($locator->getArguments()) as $mapper) {
            /** @var Reference $mapper * */
            $this->checkInterface($mapper->__toString(), FilterMapperInterface::class);
        }
    }
}
