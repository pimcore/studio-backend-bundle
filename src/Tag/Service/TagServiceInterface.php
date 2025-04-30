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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidParentIdException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
interface TagServiceInterface
{
    /**
     * @throws NotFoundException
     */
    public function getTag(int $id): Tag;

    /**
     * @return array<int, Tag>
     */
    public function getTagsForElement(ElementParameters $tagElement): array;

    /**
     * @return array<int>
     */
    public function getTagIdsForElement(ElementParameters $tagElement): array;

    /**
     * @throws NotFoundException
     */
    public function assignTagToElement(ElementParameters $tagElement, int $tagId): void;

    public function batchAssignTagsToElements(BatchCollectionParameters $collection, UserInterface $user): void;

    public function batchReplaceTagsToElements(BatchCollectionParameters $collection, UserInterface $user): void;

    /**
     * @throws NotFoundException
     */
    public function unassignTagFromElement(ElementParameters $tagElement, int $tagId): void;

    /**
     * @return array<int, Tag>
     */
    public function listTags(TagsParameters $parameters): array;

    /**
     * @throws InvalidParentIdException
     * @throws NotFoundException
     */
    public function createTag(CreateTagParameters $tag): Tag;

    /**
     * @throws NotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $parameters): void;

    /**
     * @throws ElementDeletingFailedException
     * @throws NotFoundException
     */
    public function deleteTag(int $id): int;
}
