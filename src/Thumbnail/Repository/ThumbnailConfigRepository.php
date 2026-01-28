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
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;

/**
 * @internal
 */
final readonly class ThumbnailConfigRepository implements ThumbnailConfigRepositoryInterface
{
    public function imageConfigExists(string $name): bool
    {
        return $this->configExists(ImageConfig::class, $name);
    }

    public function videoConfigExists(string $name): bool
    {
        return $this->configExists(VideoConfig::class, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function updateImageConfig(ImageConfig $config, UpdateThumbnailConfig $parameters): ImageConfig
    {
        return $this->updateConfig($config, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVideoConfig(VideoConfig $config, UpdateThumbnailConfig $parameters): VideoConfig
    {
        return $this->updateConfig($config, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function checkIfWriteable(ImageConfig|VideoConfig $config): void
    {
        if (!$config->isWriteable()) {
            throw new NotWriteableException(
                'thumbnail',
                'The thumbnail configuration "' . $config->getName() . '" is not writeable.'
            );
        }
    }

    /**
     * @param class-string<ImageConfig|VideoConfig> $configClass
     */
    private function configExists(string $configClass, string $name): bool
    {
        try {
            $config = $configClass::getByName($name);

            return $config !== null;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * @throws InvalidArgumentException|NotWriteableException
     */
    private function updateConfig(
        ImageConfig|VideoConfig $config,
        UpdateThumbnailConfig $parameters
    ): ImageConfig|VideoConfig
    {
        $this->checkIfWriteable($config);
        $this->applySettings($config, $parameters);
        $config->resetItems();
        $this->applyMediaItems($config, $parameters);
        $config->save();

        return $config;
    }

    private function applySettings(ImageConfig|VideoConfig $config, UpdateThumbnailConfig $parameters): void
    {
        foreach ($parameters->getSettings() as $key => $value) {
            if ($key === 'name') {
                continue;
            }

            $setter = 'set' . ucfirst($key);
            if (method_exists($config, $setter)) {
                $config->$setter($value);
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function applyMediaItems(ImageConfig|VideoConfig $config, UpdateThumbnailConfig $parameters): void
    {
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
                $method = $item['method'];
                unset($item['method']);

                $config->addItem($method, $item['arguments'], htmlspecialchars($mediaName));
            }
        }
    }
}
