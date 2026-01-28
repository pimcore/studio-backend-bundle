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
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\Config\Listing as ImageThumbnailListing;

/**
 * @internal
 */
final readonly class ImageThumbnailRepository implements ImageThumbnailRepositoryInterface
{
    public function __construct(
        private ThumbnailConfigRepositoryInterface $thumbnailConfigRepository,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function listImageThumbnails(): array
    {
        $thumbnailListing = new ImageThumbnailListing();
        $thumbnailListing->setFilter(function (Config $config) {
            return $config->isDownloadable();
        });

        return $thumbnailListing->getThumbnails();
    }

    /**
     * {@inheritdoc}
     */
    public function listImageThumbnailConfigs(): array
    {
        return (new ImageThumbnailListing())->getThumbnails();
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
            throw new NotFoundException(type: 'image thumbnail configuration', id: $name, previous: $exception);
        }

        return $config;
    }

    public function exists(string $name): bool
    {
        return $this->thumbnailConfigRepository->imageConfigExists($name);
    }

    public function add(string $name): Config
    {
        $config = new Config();
        $this->thumbnailConfigRepository->checkIfWriteable($config);
        $config->setName($name);
        $config->save();

        return $config;
    }

    public function update(Config $config, UpdateThumbnailConfig $parameters): Config
    {
        return $this->thumbnailConfigRepository->updateImageConfig($config, $parameters);
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
