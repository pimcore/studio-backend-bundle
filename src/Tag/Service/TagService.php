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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidParentIdException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Tag\Event\TagEvent;
use Pimcore\Bundle\StudioBackendBundle\Tag\Hydrator\TagHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Repository\TagRepositoryInterface;
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
     * @throws NotFoundException
     */
    public function getTag(int $id): Tag
    {
        $tag = $this->tagHydrator->hydrateRecursive($this->tagRepository->getTagById($id));
        $this->dispatchTagEvent($tag);

        return $tag;
    }

    /**
     * @return array<int, Tag>
     */
    public function getTagsForElement(ElementParameters $tagElement): array
    {
        $result = [];
        foreach ($this->tagRepository->getTagsForElement($tagElement) as $tag) {
            $result[$tag->getId()] = $this->tagHydrator->hydrate($tag);
            $this->dispatchTagEvent($result[$tag->getId()]);
        }

        return $result;
    }

    /**
     * @throws NotFoundException
     */
    public function assignTagToElement(ElementParameters $tagElement, int $tagId): void
    {
        $this->tagRepository->assignTagToElement($tagElement, $tagId);
    }

    public function batchAssignTagsToElements(BatchCollectionParameters $collection): void
    {
        $this->tagRepository->batchAssignTagsToElements($collection);
    }

    public function batchReplaceTagsToElements(BatchCollectionParameters $collection): void
    {
        $this->tagRepository->batchReplaceTagsToElements($collection);
    }

    /**
     * @return array<int, Tag>
     */
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
     * @throws NotFoundException
     */
    public function createTag(CreateTagParameters $tag): Tag
    {
        if ($tag->getParentId() !== 0) {
            try {
                $this->tagRepository->getTagById($tag->getParentId());
            } catch (NotFoundException) {
                throw new InvalidParentIdException($tag->getParentId());
            }
        }

        return $this->getTag($this->tagRepository->addTag($tag)->getId());
    }

    /**
     * @throws NotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $parameters): Tag
    {
        return $this->getTag($this->tagRepository->updateTag($id, $parameters)->getId());
    }

    /**
     * @throws ElementDeletingFailedException
     * @throws NotFoundException
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

    public function unassignTagFromElement(ElementParameters $tagElement, int $tagId): void
    {
        $this->tagRepository->unassignTagFromElement($tagElement, $tagId);
    }
}
