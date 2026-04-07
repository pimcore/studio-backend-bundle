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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;

/**
 * @internal
 */
interface ThumbnailConfigRepositoryInterface
{
    public function imageConfigExists(string $name): bool;

    public function videoConfigExists(string $name): bool;

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    public function updateImageConfig(ImageConfig $config, UpdateThumbnailConfig $parameters): ImageConfig;

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    public function updateVideoConfig(VideoConfig $config, UpdateThumbnailConfig $parameters): VideoConfig;

    /**
     * @throws NotWriteableException
     */
    public function checkIfWriteable(ImageConfig|VideoConfig $config): void;
}
