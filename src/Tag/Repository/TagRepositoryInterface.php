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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Element\Tag\Listing as TagListing;

/**
 * @internal
 */
interface TagRepositoryInterface
{
    /**
     * @throws NotFoundException
     */
    public function getTagById(int $id): Tag;

    /**
     * @return array<int, Tag>
     */
    public function getTagsForElement(ElementParameters $tagElement): array;

    /**
     * @throws NotFoundException
     */
    public function assignTagToElement(ElementParameters $tagElement, int $tagId): void;

    /**
     * @throws NotFoundException
     */
    public function unassignTagFromElement(ElementParameters $tagElement, int $tagId): void;

    public function batchAssignTagsToElements(BatchCollectionParameters $collection): void;

    public function batchReplaceTagsToElements(BatchCollectionParameters $collection): void;

    public function listTags(TagsParameters $parameters): TagListing;

    public function addTag(CreateTagParameters $params): Tag;

    /**
     * @throws NotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $params): void;

    /**
     * @throws ElementDeletingFailedException
     * @throws NotFoundException
     */
    public function deleteTag(int $id): void;
}
