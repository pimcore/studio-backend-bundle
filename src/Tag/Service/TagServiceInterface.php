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

use Pimcore\Bundle\StudioBackendBundle\Exception\ElementDeletingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ElementNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidParentIdException;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchCollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\UpdateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;

/**
 * @internal
 */
interface TagServiceInterface
{
    /**
     * @throws ElementNotFoundException
     */
    public function getTag(int $id): Tag;

    /**
     * @return array<int, Tag>
     */
    public function getTagsForElement(ElementParameters $tagElement): array;

    /**
     * @throws ElementNotFoundException
     */
    public function assignTagToElement(ElementParameters $tagElement, int $tagId): void;

    public function batchAssignTagsToElements(BatchCollectionParameters $collection): void;

    public function batchReplaceTagsToElements(BatchCollectionParameters $collection): void;

    /**
     * @throws ElementNotFoundException
     */
    public function unassignTagFromElement(ElementParameters $tagElement, int $tagId): void;

    /**
     * @return array<int, Tag>
     */
    public function listTags(TagsParameters $parameters): array;

    /**
     * @throws InvalidParentIdException
     * @throws ElementNotFoundException
     */
    public function createTag(CreateTagParameters $tag): Tag;

    /**
     * @throws ElementNotFoundException
     */
    public function updateTag(int $id, UpdateTagParameters $parameters): Tag;

    /**
     * @throws ElementDeletingFailedException
     * @throws ElementNotFoundException
     */
    public function deleteTag(int $id): int;
}
