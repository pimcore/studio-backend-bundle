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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Video;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ThumbnailConfig;

final class ThumbnailConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.thumbnail.video_config';

    public function __construct(
        private readonly ThumbnailConfig $thumbnailConfig
    ) {
        parent::__construct($thumbnailConfig);
    }

    public function getThumbnailConfig(): ThumbnailConfig
    {
        return $this->thumbnailConfig;
    }
}
