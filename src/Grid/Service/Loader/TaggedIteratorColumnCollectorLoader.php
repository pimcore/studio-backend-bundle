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
