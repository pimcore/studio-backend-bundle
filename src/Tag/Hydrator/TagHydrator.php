<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Icon\Service\IconServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Model\Element\Tag as ElementTag;

/**
 * @internal
 */
final readonly class TagHydrator implements TagHydratorInterface
{
    public function __construct(private readonly IconServiceInterface $iconService)
    {
    }

    public function hydrate(ElementTag $tag): Tag {
        return new Tag(
            id: $tag->getId(),
            parentId: $tag->getParentId(),
            text: $tag->getName(),
            path: $tag->getNamePath(),
            hasChildren: $tag->hasChildren(),
            iconName: $this->iconService->getIconForTag()
        );
    }

    public function hydrateRecursive(ElementTag $tag): Tag {
        $children = [];
        foreach ($tag->getChildren() as $child) {
            $children[] = $this->hydrateRecursive($child);
        }

        $result = $this->hydrate($tag);
        $result->setChildren($children);
        return $result;
    }

    /**
     * @param array<int, ElementTag> $tags
     * @return array<int, Tag>
     */
    public function hydrateNestedList(array $tags): array {
        $tagMap = [];
        $nestedTags = [];

        foreach ($tags as $tag) {
            $tagMap[$tag->getId()] = $this->hydrate($tag);
        }

        foreach ($tagMap as $tag) {
            if ($tag->getParentId() === 0 || !array_key_exists($tag->getParentId(), $tagMap)) {
                $nestedTags[] = $tag;
                continue;
            }
            $tagMap[$tag->getParentId()]->addChild($tag);
        }
        return $nestedTags;
    }
}