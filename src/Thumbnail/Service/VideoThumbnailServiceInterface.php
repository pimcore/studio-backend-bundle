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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailFolder;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailConfigDetail;

/**
 * @internal
 */
interface VideoThumbnailServiceInterface
{
    public function listThumbnails(): ThumbnailCollection;

    /**
     * @return ThumbnailConfig[]|ThumbnailFolder[]
     */
    public function getTree(): array;

    /**
     * @throws NotFoundException
     */
    public function getThumbnail(string $name): VideoThumbnailConfigDetail;

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function deleteThumbnail(string $name): void;

    /**
     * @throws ElementExistsException|NotWriteableException
     */
    public function addThumbnail(string $name): VideoThumbnailConfigDetail;

    /**
     * @throws InvalidArgumentException|NotFoundException|NotWriteableException
     */
    public function updateThumbnail(string $name, UpdateThumbnailConfig $parameters): VideoThumbnailConfigDetail;
}
