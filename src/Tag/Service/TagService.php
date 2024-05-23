<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidParentIdException;
use Pimcore\Bundle\StudioBackendBundle\Tag\Hydrator\TagHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Repository\TagRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;

/**
 * @internal
 */
final readonly class TagService implements TagServiceInterface
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private TagHydratorInterface $tagHydrator
    ) {
    }

    public function getTag(int $id): Tag
    {
        return $this->tagHydrator->hydrateRecursive($this->tagRepository->getTagById($id));
    }

    public function listTags(TagsParameters $parameters): array
    {
        $tags = $this->tagRepository->listTags($parameters);
        return $this->tagHydrator->hydrateNestedList($tags->load());
    }

    public function createTag(CreateTagParameters $tag): Tag
    {
        if ($tag->getParentId() !== 0) {
            try {
                $this->tagRepository->getTagById($tag->getParentId());
            } catch (ElementNotFoundException) {
                throw new InvalidParentIdException($tag->getParentId());
            }
        }

        return $this->tagHydrator->hydrate($this->tagRepository->addTag($tag));
    }

    public function updateTag(int $id, UpdateTagParameters $parameters): Tag
    {
        $tag = $this->tagRepository->getTagById($id);

        return $this->tagHydrator->hydrate($this->tagRepository->updateTag($id, $parameters));
    }
}