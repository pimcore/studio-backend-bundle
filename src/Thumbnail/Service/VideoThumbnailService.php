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
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\ThumbnailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Video\ThumbnailConfigDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Video\ThumbnailConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Video\ThumbnailFolderEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\Video\ConfigDetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository\VideoThumbnailRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\VideoThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Thumbnails;
use Pimcore\Model\Asset\Video\Thumbnail\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class VideoThumbnailService implements VideoThumbnailServiceInterface
{
    private const string ICON = 'video-thumbnail';

    public function __construct(
        private ConfigDetailHydratorInterface $configDetailHydrator,
        private VideoThumbnailRepositoryInterface $videoThumbnailRepository,
        private ThumbnailConfigServiceInterface $thumbnailConfigService,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function listThumbnails(): ThumbnailCollection
    {
        $items = [
            new Thumbnail(
                Thumbnails::DEFAULT_THUMBNAIL_ID->value,
                Thumbnails::DEFAULT_THUMBNAIL_TEXT->value
            ),
        ];

        $configurations = $this->videoThumbnailRepository->listVideoThumbnailConfigs();
        foreach ($configurations as $config) {
            $items[] = $this->hydrateListItem($config);
        }

        return new ThumbnailCollection($items);
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(): array
    {
        $configurations = $this->videoThumbnailRepository->listVideoThumbnailConfigs();
        $groupedThumbnails = $this->thumbnailConfigService->groupThumbnails(
            $configurations,
            self::ICON,
            ThumbnailConfigEvent::class
        );

        return $this->thumbnailConfigService->buildTree($groupedThumbnails, ThumbnailFolderEvent::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail(string $name): VideoThumbnailConfigDetail
    {
        $configuration = $this->videoThumbnailRepository->getByName($name);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteThumbnail(string $name): void
    {
        $this->videoThumbnailRepository->delete($name);
    }

    /**
     * {@inheritdoc}
     */
    public function addThumbnail(string $name): VideoThumbnailConfigDetail
    {
        if ($this->videoThumbnailRepository->exists($name)) {
            throw new ElementExistsException(
                sprintf('Video thumbnail configuration with name %s already exists', $name)
            );
        }
        $configuration = $this->videoThumbnailRepository->add($name);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function updateThumbnail(string $name, UpdateThumbnailConfig $parameters): VideoThumbnailConfigDetail
    {
        $configuration = $this->videoThumbnailRepository->getByName($name);
        $configuration = $this->videoThumbnailRepository->update($configuration, $parameters);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    private function hydrateListItem(Config $config): Thumbnail
    {
        $thumbnail = new Thumbnail($config->getName(), $config->getName());

        $this->eventDispatcher->dispatch(
            new ThumbnailEvent($thumbnail),
            ThumbnailEvent::EVENT_NAME
        );

        return $thumbnail;
    }

    private function hydrateThumbnailConfigDetail(Config $configuration): VideoThumbnailConfigDetail
    {
        $thumbnailConfig = $this->configDetailHydrator->hydrate($configuration);

        $this->eventDispatcher->dispatch(
            new ThumbnailConfigDetailEvent($thumbnailConfig),
            ThumbnailConfigDetailEvent::EVENT_NAME
        );

        return $thumbnailConfig;
    }
}
