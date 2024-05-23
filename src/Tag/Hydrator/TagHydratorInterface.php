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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Model\Element\Tag as ElementTag;

/**
 * @internal
 */
interface TagHydratorInterface
{
    public function hydrate(ElementTag $tag): Tag;

    public function hydrateRecursive(ElementTag $tag): Tag;

    /**
     * @param array<int, ElementTag> $tags
     *
     * @return array<int, Tag>
     */
    public function hydrateNestedList(array $tags): array;
}
