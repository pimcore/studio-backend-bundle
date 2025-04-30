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

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface as DataIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Loader\TaggedIteratorAdapter as DataIndexFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\MustImplementInterfaceTrait;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @internal
 */
final class DataIndexFilterPass implements CompilerPassInterface
{
    use MustImplementInterfaceTrait;

    /**
     * @throws MustImplementInterfaceException
     */
    public function process(ContainerBuilder $container): void
    {
        $dataIndexFilter = array_keys(
            [
                ... $container->findTaggedServiceIds(DataIndexFilter::FILTER_TAG),
                ... $container->findTaggedServiceIds(DataIndexFilter::FILTER_ASSET_TAG),
                ... $container->findTaggedServiceIds(DataIndexFilter::FILTER_DATA_OBJECT_TAG),
                ... $container->findTaggedServiceIds(DataIndexFilter::FILTER_DOCUMENT_TAG),

            ]
        );

        foreach ($dataIndexFilter as $filter) {
            $this->checkInterface($filter, DataIndexFilterInterface::class);
        }
    }
}
