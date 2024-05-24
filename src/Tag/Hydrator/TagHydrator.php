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
    public function __construct(private IconServiceInterface $iconService)
    {
    }

    public function hydrate(ElementTag $tag): Tag
    {
        return new Tag(
            id: $tag->getId(),
            parentId: $tag->getParentId(),
            text: $tag->getName(),
            path: $tag->getNamePath(),
            hasChildren: $tag->hasChildren(),
            iconName: $this->iconService->getIconForTag()
        );
    }

    public function hydrateRecursive(ElementTag $tag): Tag
    {
        $result = $this->hydrate($tag);

        $children = [];
        foreach ($tag->getChildren() as $child) {
            $children[] = $this->hydrateRecursive($child);
        }

        $result->setChildren($children);
        return $result;
    }
}
