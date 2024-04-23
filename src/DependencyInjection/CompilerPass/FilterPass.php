<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioApiBundle\DependencyInjection\CompilerPass;

use Pimcore\Bundle\StudioApiBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioApiBundle\Filter\FilterInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\Filter\Loader\TaggedIteratorAdapter;
use Pimcore\Bundle\StudioApiBundle\Util\Traits\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class FilterPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = array_keys(
            [
                ... $container->findTaggedServiceIds(TaggedIteratorAdapter::FILTER_TAG),
                ... $container->findTaggedServiceIds(TaggedIteratorAdapter::FILTER_ASSET_TAG),
                ... $container->findTaggedServiceIds(TaggedIteratorAdapter::FILTER_DATA_OBJECT_TAG),
                ... $container->findTaggedServiceIds(TaggedIteratorAdapter::FILTER_DOCUMENT_TAG),

            ]
        );

        foreach ($taggedServices as $environmentType) {
            $this->checkInterface($environmentType, FilterInterface::class);
        }
    }
}
