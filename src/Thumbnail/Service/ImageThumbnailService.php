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
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Image\ThumbnailConfigDetailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Image\ThumbnailConfigEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Image\ThumbnailFolderEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\ThumbnailEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\Image\ConfigDetailHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\ThumbnailConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\ThumbnailFolderHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository\ImageThumbnailRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ImageThumbnailConfigDetail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\Thumbnail;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailFolder;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\UpdateThumbnailConfig;
use Pimcore\Model\Asset\Image\Thumbnail\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class ImageThumbnailService implements ImageThumbnailServiceInterface
{
    private const string ICON = 'image-thumbnail';

    public function __construct(
        private ConfigDetailHydratorInterface $configDetailHydrator,
        private ImageThumbnailRepositoryInterface $imageThumbnailRepository,
        private ThumbnailConfigHydratorInterface $thumbnailConfigHydrator,
        private ThumbnailFolderHydratorInterface $thumbnailFolderHydrator,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function listThumbnails(): ThumbnailCollection
    {
        $collection = [];
        $configurations = $this->imageThumbnailRepository->listImageThumbnails();
        foreach ($configurations as $config) {
            $collection[] = $this->hydrateListItem($config);
        }

        return new ThumbnailCollection($collection);
    }

    /**
     * {@inheritdoc}
     */
    public function getTree(): array
    {
        $configurations = $this->imageThumbnailRepository->listImageThumbnailConfigs();
        $groupedThumbnails = $this->groupThumbnails($configurations);

        return $this->buildTree($groupedThumbnails);
    }

    /**
     * {@inheritdoc}
     */
    public function getThumbnail(string $name): ImageThumbnailConfigDetail
    {
        $configuration = $this->imageThumbnailRepository->getByName($name);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteThumbnail(string $name): void
    {
        $this->imageThumbnailRepository->delete($name);
    }

    /**
     * {@inheritdoc}
     */
    public function addThumbnail(string $name): ImageThumbnailConfigDetail
    {
        if ($this->imageThumbnailRepository->exists($name)) {
            throw new ElementExistsException(
                sprintf('Image thumbnail configuration with name %s already exists', $name)
            );
        }
        $configuration = $this->imageThumbnailRepository->add($name);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    /**
     * {@inheritdoc}
     */
    public function updateThumbnail(string $name, UpdateThumbnailConfig $parameters): ImageThumbnailConfigDetail
    {
        $configuration = $this->imageThumbnailRepository->getByName($name);
        $configuration = $this->imageThumbnailRepository->update($configuration, $parameters);

        return $this->hydrateThumbnailConfigDetail($configuration);
    }

    /**
     * @param Config[] $configurations
     */
    private function groupThumbnails(array $configurations): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($configurations as $configuration) {
            $group = $configuration->getGroup();

            if (!$group) {
                $ungrouped[] = $this->hydrateThumbnailConfig($configuration);

                continue;
            }

            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = $this->hydrateThumbnailConfig($configuration);
        }

        return ['groups' => $groups, 'ungrouped' => $ungrouped];
    }

    private function buildTree(array $groupedThumbnails): array
    {
        $tree = $groupedThumbnails['ungrouped'];

        foreach ($groupedThumbnails['groups'] as $groupName => $children) {
            $tree[] = $this->hydrateFolderNode($groupName, $children);
        }

        usort($tree, static fn ($a, $b) => strcasecmp($a->getName(), $b->getName()));

        return $tree;
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

    private function hydrateThumbnailConfigDetail(Config $configuration): ImageThumbnailConfigDetail
    {
        $thumbnailConfig = $this->configDetailHydrator->hydrate($configuration);

        $this->eventDispatcher->dispatch(
            new ThumbnailConfigDetailEvent($thumbnailConfig),
            ThumbnailConfigDetailEvent::EVENT_NAME
        );

        return $thumbnailConfig;
    }

    private function hydrateThumbnailConfig(Config $configuration): ThumbnailConfig
    {
        $thumbnailConfig = $this->thumbnailConfigHydrator->hydrate($configuration, self::ICON);

        $this->eventDispatcher->dispatch(
            new ThumbnailConfigEvent($thumbnailConfig),
            ThumbnailConfigEvent::EVENT_NAME
        );

        return $thumbnailConfig;
    }

    private function hydrateFolderNode(string $name, array $children): ThumbnailFolder
    {
        $thumbnailFolder = $this->thumbnailFolderHydrator->hydrate($name, $children);

        $this->eventDispatcher->dispatch(
            new ThumbnailFolderEvent($thumbnailFolder),
            ThumbnailFolderEvent::EVENT_NAME
        );

        return $thumbnailFolder;
    }
}
