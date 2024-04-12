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

namespace Pimcore\Bundle\StudioApiBundle\Service\Filter\Loader;

use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements FilterLoaderInterface
{
    public function __construct(
        #[TaggedIterator('pimcore.studio_api.collection.filter')]
        private readonly iterable $taggedServices
    ) {
    }

    public function loadFilters(): array
    {
        return [... $this->taggedServices];
    }
}
