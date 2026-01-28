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

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailFolder;
use Pimcore\Model\Asset\Image\Thumbnail\Config as ImageConfig;
use Pimcore\Model\Asset\Video\Thumbnail\Config as VideoConfig;

/**
 * @internal
 */
interface ThumbnailConfigServiceInterface
{
    public function groupThumbnails(array $configurations, string $icon, string $eventClass): array;

    /**
     * @return ThumbnailConfig[]|ThumbnailFolder[]
     */
    public function buildTree(array $groupedThumbnails, string $folderEventClass): array;

    public function hydrateThumbnailConfig(
        ImageConfig|VideoConfig $configuration,
        string $icon,
        string $eventClass
    ): ThumbnailConfig;

    /**
     * @param ThumbnailConfig[] $children
     */
    public function hydrateFolderNode(string $name, array $children, string $eventClass): ThumbnailFolder;
}
