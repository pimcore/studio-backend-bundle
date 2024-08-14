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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Filter\Loader;

use Pimcore\Bundle\StudioBackendBundle\Element\Filter\FilterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Filter\Filters;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public const FILTER_TAG = 'pimcore.studio_backend.element_listing.filter';

    public function __construct(
        #[TaggedIterator(self::FILTER_TAG)]
        private readonly iterable $taggedFilters,
    ) {
    }

    public function loadFilters(): Filters
    {
        return new Filters(
            [... $this->taggedFilters],
        );
    }
}
