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
