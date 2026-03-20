<?php
declare(strict_types=1);

/**
 * This source file is available under the terms of the
 * Pimcore Open Core License (POCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (https://www.pimcore.com)
 *  @license    Pimcore Open Core License (POCL)
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Tag\TagResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Element\Tag\Listing as TagListing;

/**
 * @internal
 */
final readonly class TagRepository implements TagRepositoryInterface
{
    use ElementProviderTrait;

    public function __construct(
        private TagResolverInterface $tagResolver
    ) {
    }

    /**
     * @return array<int, Tag>
     */
    public function getTagsForElement(ElementParameters $tagElement): array
    {
        return $this->tagResolver->getTagsForElement($tagElement->getType(), $tagElement->getId());
    }

    /**
     * @throws NotFoundException
     */
    public function assignTagToElement(ElementParameters $tagElement, int $tagId): void
    {
        $tag = $this->getTagById($tagId);
        $this->tagResolver->assignTagToElement($tagElement->getType(), $tagElement->getId(), $tag);
    }

    /**
     * @throws NotFoundException
     */
    public function unassignTagFromElement(ElementParameters $tagElement, int $tagId): void
    {
        $tag = $this->getTagById($tagId);
        $this->tagResolver->unassignTagFromElement($tagElement->getType(), $tagElement->getId(), $tag);
    }

    public function batchAssignTagsToElements(BatchCollectionParameters $collection): void
    {
        $this->tagResolver->batchAssignTagsToElements(
            $collection->getType(),
            $collection->getElementIds(),
            $collection->getTagIds()
        );
    }

    public function batchReplaceTagsToElements(BatchCollectionParameters $collection): void
    {
        $this->tagResolver->batchReplaceTagsForElements(
            $collection->getType(),
            $collection->getElementIds(),
            $collection->getTagIds()
        );
    }

    /**
     * @throws NotFoundException
     */
    public function getTagById(int $id): Tag
    {
        $tag = $this->tagResolver->getById($id);
        if (!$tag) {
            throw new NotFoundException('Tag', $id);
        }

        return $tag;
    }

    public function listTags(TagsParameters $parameters): TagListing
    {
        $tagList = new TagListing();
        $tagList->setOrderKey('name');

        if ($parameters->getParentId() !== null) {
            $tagList->setCondition('parentId = ?', $parameters->getParentId());
        }

        if ($parameters->getFilter() === null) {
            return $tagList;
        }

        $filterTagList = new TagListing();
        $filterTagList->setCondition(
            'LOWER(`name`) LIKE ?',
            ['%' . $filterTagList->escapeLike(mb_strtolower($parameters->getFilter())) . '%']
        );

        $filterIds = [0];
        foreach ($filterTagList->load() as $filterTag) {
            $filterIds[] = $filterTag->getId();
            if ($filterTag->getParentId() === 0) {
                continue;
            }

            $ids = explode('/', $filterTag->getIdPath());
            foreach ($ids as $id) {
                if ($id !== '') {
                    $filterIds[] = (int)$id;
                }
            }
        }

        $filterIds = array_unique($filterIds);
        $tagList->setConditionVariablesFromSetCondition([]);
        $tagList->setCondition('id IN('.implode(',', $filterIds).')');

        return $tagList;
    }

    public function addTag(CreateTagParameters $params): Tag
    {
        $new = new Tag();
        $new->setParentId($params->getParentId());
        $new->setName($params->getName());
        $new->save();

        return $new;
    }

    /**
     * @throws NotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $params): void
    {
        $tag = $this->getTagById($id);

        if ($params->getParentId() !== null) {
            $tag->setParentId($params->getParentId());
        }

        if ($params->getName() !== null) {
            $tag->setName($params->getName());
        }
        $tag->save();
    }

    /**
     * @throws ElementDeletingFailedException
     * @throws NotFoundException
     */
    public function deleteTag(int $id): void
    {
        $tag = $this->getTagById($id);

        try {
            $tag->delete();
        } catch (Exception $e) {
            throw new ElementDeletingFailedException($id, $e->getMessage());
        }
    }
}
