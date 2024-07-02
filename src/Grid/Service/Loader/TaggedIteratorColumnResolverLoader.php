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

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnResolverLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorColumnResolverLoader implements ColumnResolverLoaderInterface
{
    public const COLUMN_RESOLVER_TAG = 'pimcore.studio_backend.grid_column_resolver';

    /**
     * @param iterable<ColumnResolverInterface> $taggedColumnResolvers
     */
    public function __construct(
        #[TaggedIterator(self::COLUMN_RESOLVER_TAG)]
        private iterable $taggedColumnResolvers,
    ) {
    }

    /**
     * @return array<string, ColumnResolverInterface>
     */
    public function loadColumnResolvers(): array
    {
        $columnResolvers = [];
        foreach ($this->taggedColumnResolvers as $columnResolver) {
            $columnResolvers[$columnResolver->getType()] = $columnResolver;
        }

        return $columnResolvers;
    }
}
