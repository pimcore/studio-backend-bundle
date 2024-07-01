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

use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnDefinitionInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnDefinitionLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final readonly class TaggedIteratorColumnDefinitionLoader implements ColumnDefinitionLoaderInterface
{
    public const COLUMN_DEFINITION_TAG = 'pimcore.studio_backend.grid_column_definition';

    /**
     * @param iterable<ColumnDefinitionInterface> $taggedColumnDefinitions
     */
    public function __construct(
        #[TaggedIterator(self::COLUMN_DEFINITION_TAG)]
        private iterable $taggedColumnDefinitions,
    ) {
    }

    /**
     * @return array<string, ColumnDefinitionInterface>
     */
    public function loadColumnDefinitions(): array
    {
        $columnDefinitions = [];
        foreach ($this->taggedColumnDefinitions as $columnDefinition) {
            $columnDefinitions[$columnDefinition->getType()] = $columnDefinition;
        }

        return $columnDefinitions;
    }
}
