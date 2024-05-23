<?php
declare(strict_types=1);

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
     * @return array<int, Tag>
     */
    public function hydrateNestedList(array $tags): array;
}