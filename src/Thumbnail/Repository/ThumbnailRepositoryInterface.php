<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Repository;

use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailCollection;

/**
 * @internal
 */
interface ThumbnailRepositoryInterface
{
    public function listVideoThumbnails(
    ): ThumbnailCollection;

    public function listImageThumbnails(
    ): ThumbnailCollection;
}
