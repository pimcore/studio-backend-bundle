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

namespace Pimcore\Bundle\StudioBackendBundle\Grid\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnCollectorInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnCollectorLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorColumnCollectorLoader implements ColumnCollectorLoaderInterface
{
    public const GRID_COLUMN_COLLECTOR_TAG = 'pimcore.studio_backend.grid_column_collector';

    /**
     * @param iterable<ColumnCollectorInterface> $taggedColumnCollectors
     */
    public function __construct(
        #[TaggedIterator(self::GRID_COLUMN_COLLECTOR_TAG)]
        private iterable $taggedColumnCollectors,
    ) {
    }

    /**
     * @return array<string, ColumnCollectorInterface>
     */
    public function loadColumnCollectors(): array
    {
        $columnCollectors = [];
        foreach ($this->taggedColumnCollectors as $columnCollector) {
            $columnCollectors[$columnCollector->getCollectorName()] = $columnCollector;
        }

        return $columnCollectors;
    }
}
