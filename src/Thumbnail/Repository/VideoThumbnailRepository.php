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

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config;
use Pimcore\Model\Asset\Video\Thumbnail\Config\Listing as VideoThumbnailListing;

/**
 * @internal
 */
final readonly class VideoThumbnailRepository implements VideoThumbnailRepositoryInterface
{
    public function __construct(
        private ThumbnailConfigRepositoryInterface $thumbnailConfigRepository
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listVideoThumbnailConfigs(): array
    {
        return (new VideoThumbnailListing())->getThumbnails();
    }

    /**
     * {@inheritdoc}
     */
    public function getByName(string $name): Config
    {
        $exception = null;
        $config = null;

        try {
            $config = Config::getByName($name);
        } catch (Exception $e) {
            $exception = $e;
        }

        if (!$config || $exception) {
            throw new NotFoundException(type: 'video thumbnail configuration', id: $name, previous: $exception);
        }

        return $config;
    }

    public function exists(string $name): bool
    {
        return $this->thumbnailConfigRepository->videoConfigExists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name): Config
    {
        $config = new Config();
        $this->thumbnailConfigRepository->checkIfWriteable($config);
        $config->setName($name);
        $config->save();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Config $config, UpdateThumbnailConfig $parameters): Config
    {
        return $this->thumbnailConfigRepository->updateVideoConfig($config, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name): void
    {
        $config = $this->getByName($name);
        $this->thumbnailConfigRepository->checkIfWriteable($config);
        $config->delete();
    }
}
