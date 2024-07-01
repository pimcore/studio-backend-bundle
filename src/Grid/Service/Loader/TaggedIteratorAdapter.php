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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Grid\Adapter\ColumnAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\AdapterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorAdapter implements AdapterLoaderInterface
{
    public const ADAPTER_TAG = 'pimcore.studio_backend.grid_column_adapter';

    /**
     * @param iterable<ColumnAdapterInterface> $taggedAdapter
     */
    public function __construct(
        #[TaggedIterator(self::ADAPTER_TAG)]
        private iterable $taggedAdapter,
    ) {
    }

    /**
     * @return array<string, ColumnAdapterInterface>
     */
    public function loadAdapters(): array
    {
        $adapters = [];
        foreach ($this->taggedAdapter as $adapter) {
            $adapters[$adapter->getType()] = $adapter;
        }

        return $adapters;
    }
}
