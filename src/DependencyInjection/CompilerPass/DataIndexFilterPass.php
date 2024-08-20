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

namespace Pimcore\Bundle\StudioBackendBundle\DependencyInjection\CompilerPass;

use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\FilterInterface as DataIndexFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Filter\Loader\TaggedIteratorAdapter as DataIndexFilter;
use Pimcore\Bundle\StudioBackendBundle\Exception\MustImplementInterfaceException;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\MustImplementInterfaceTrait;
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
