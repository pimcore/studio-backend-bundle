<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Repository;

use Pimcore\Bundle\StudioBackendBundle\Tag\Request\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\UpdateTagParameters;
use Pimcore\Model\Element\Tag;
use Pimcore\Model\Element\Tag\Listing as TagListing;

/**
 * @internal
 */
interface TagRepositoryInterface
{
    public function getTagById(int $id): Tag;
    public function listTags(TagsParameters $parameters): TagListing;
    public function addTag(CreateTagParameters $params): Tag;
    public function updateTag(int $id, UpdateTagParameters $params): Tag;
}