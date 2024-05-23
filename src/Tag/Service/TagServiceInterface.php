<?php
declare(strict_types=1);

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service;

use Pimcore\Bundle\StudioBackendBundle\Tag\Request\CreateTagParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Request\TagsParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Schema\Tag;

/**
 * @internal
 */
interface TagServiceInterface
{
    public function getTag(int $id): Tag;
    public function listTags(TagsParameters $parameters): array;
    public function createTag(CreateTagParameters $tag): Tag;
}