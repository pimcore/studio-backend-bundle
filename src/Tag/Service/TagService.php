<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidParentIdException;
use Pimcore\Bundle\StudioBackendBundle\Tag\Event\TagEvent;
use Pimcore\Bundle\StudioBackendBundle\Tag\Hydrator\TagHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Repository\TagRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class TagService implements TagServiceInterface
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private TagHydratorInterface $tagHydrator,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws ElementNotFoundException
     */
    public function getTag(int $id): Tag
    {
        $tag = $this->tagHydrator->hydrateRecursive($this->tagRepository->getTagById($id));
        $this->dispatchTagEvent($tag);
        return $tag;
    }

    public function listTags(TagsParameters $parameters): array
    {
        $tags = $this->tagRepository->listTags($parameters);

        $tagMap = [];
        $nestedTags = [];

        foreach ($tags->load() as $tag) {
            $tagMap[$tag->getId()] = $this->tagHydrator->hydrate($tag);
            $this->dispatchTagEvent($tagMap[$tag->getId()]);
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

    /**
     * @throws InvalidParentIdException
     * @throws ElementNotFoundException
     */
    public function createTag(CreateTagParameters $tag): Tag
    {
        if ($tag->getParentId() !== 0) {
            try {
                $this->tagRepository->getTagById($tag->getParentId());
            } catch (ElementNotFoundException) {
                throw new InvalidParentIdException($tag->getParentId());
            }
        }

        return $this->getTag($this->tagRepository->addTag($tag)->getId());
    }

    /**
     * @throws ElementNotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $parameters): Tag
    {
        return $this->getTag($this->tagRepository->updateTag($id, $parameters)->getId());
    }

    /**
     * @throws ElementDeletingFailedException
     * @throws ElementNotFoundException
     */
    public function deleteTag(int $id): int
    {
        $this->tagRepository->deleteTag($id);
        return $id;
    }

    private function dispatchTagEvent(Tag $tag): void
    {
        $this->eventDispatcher->dispatch(new TagEvent($tag), TagEvent::EVENT_NAME);
    }
}
