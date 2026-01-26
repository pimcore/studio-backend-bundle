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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Pimcore\Model\Asset\Image\Thumbnail\Config\Listing as ImageThumbnailListing;

/**
 * @internal
 */
final readonly class ImageThumbnailRepository implements ImageThumbnailRepositoryInterface
{
    /**
     * @return Config[]
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
        try {
            $this->getByName($name);
        } catch (NotFoundException) {
            return false;
        }

        return true;
    }

    public function add(string $name): Config
    {
        $config = new Config();
        $this->checkIfWriteable($config);
        $config->setName($name);
        $config->save();

        return $config;
    }

    public function update(Config $config, UpdateThumbnailConfig $parameters): Config
    {
        $this->checkIfWriteable($config);
        foreach ($parameters->getSettings() as $key => $value) {
            if ($key === 'name') {
                continue;
            }

            $setter = 'set' . ucfirst($key);
            if (method_exists($config, $setter)) {
                $config->$setter($value);
            }
        }

        $config->resetItems();

        $mediaData = $parameters->getMedias();
        $mediaOrder = $parameters->getMediaOrder();
        uksort($mediaData, static function ($a, $b) use ($mediaOrder) {
            if ($a === 'default') {
                return -1;
            }

            return ($mediaOrder[$a] < $mediaOrder[$b]) ? -1 : 1;
        });

        foreach ($mediaData as $mediaName => $items) {
            if (preg_match('/["<>]/', $mediaName)) {
                throw new InvalidArgumentException('Invalid media query name');
            }

            foreach ($items as $item) {
                $type = $item['type'];
                unset($item['type']);

                $config->addItem($type, $item, htmlspecialchars($mediaName));
            }
        }

        $config->save();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $name): void
    {
        $config = $this->getByName($name);
        $this->checkIfWriteable($config);
        $config->delete();
    }

    /**
     * @throws NotWriteableException
     */
    private function checkIfWriteable(Config $config): void
    {
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'thumbnail',
                'The thumbnail configuration "' . $config->getName() . '" is not writeable.'
            );
        }
    }
}
