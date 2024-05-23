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
