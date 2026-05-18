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

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\ThumbnailConfigHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Hydrator\ThumbnailFolderHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailFolder;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class ThumbnailConfigService implements ThumbnailConfigServiceInterface
{
    public function __construct(
        private ThumbnailConfigHydratorInterface $thumbnailConfigHydrator,
        private ThumbnailFolderHydratorInterface $thumbnailFolderHydrator,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function groupThumbnails(array $configurations, string $icon, string $eventClass): array
    {
        $groups = [];
        $ungrouped = [];

        foreach ($configurations as $configuration) {
            $group = $configuration->getGroup();

            if (!$group) {
                $ungrouped[] = $this->hydrateThumbnailConfig($configuration, $icon, $eventClass);

                continue;
            }

            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = $this->hydrateThumbnailConfig($configuration, $icon, $eventClass);
        }

        return ['groups' => $groups, 'ungrouped' => $ungrouped];
    }

    /**
     * {@inheritdoc}
     */
    public function buildTree(array $groupedThumbnails, string $folderEventClass): array
    {
        $tree = $groupedThumbnails['ungrouped'];

        foreach ($groupedThumbnails['groups'] as $groupName => $children) {
            $tree[] = $this->hydrateFolderNode($groupName, $children, $folderEventClass);
        }

        usort($tree, static fn ($a, $b) => strcasecmp($a->getName(), $b->getName()));

        return $tree;
    }

    public function hydrateThumbnailConfig(
        ImageConfig|VideoConfig $configuration,
        string $icon,
        string $eventClass
    ): ThumbnailConfig {
        $thumbnailConfig = $this->thumbnailConfigHydrator->hydrate($configuration, $icon);

        $this->eventDispatcher->dispatch(
            new $eventClass($thumbnailConfig),
            $eventClass::EVENT_NAME
        );

        return $thumbnailConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateFolderNode(string $name, array $children, string $eventClass): ThumbnailFolder
    {
        $thumbnailFolder = $this->thumbnailFolderHydrator->hydrate($name, $children);

        $this->eventDispatcher->dispatch(
            new $eventClass($thumbnailFolder),
            $eventClass::EVENT_NAME
        );

        return $thumbnailFolder;
    }
}
