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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Model\Asset\Image\Thumbnail\Config;

/**
 * @internal
 */
interface ImageThumbnailRepositoryInterface
{
    /**
     * @return Config[]
     */
    public function listImageThumbnails(): array;

    /**
     * @return Config[]
     */
    public function listImageThumbnailConfigs(): array;

    /**
     * @throws NotFoundException
     */
    public function getByName(string $name): Config;

    public function exists(string $name): bool;

    /**
     * @throws NotWriteableException
     */
    public function add(string $name): Config;

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    public function update(Config $config, UpdateThumbnailConfig $parameters): Config;

    /**
     *  @throws NotFoundException|NotWriteableException
     */
    public function delete(string $name): void;
}
