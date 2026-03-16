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

namespace Pimcore\Bundle\StudioBackendBundle\Thumbnail\Event\Image;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Thumbnail\Schema\ImageThumbnailConfigDetail;

final class ThumbnailConfigDetailEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.thumbnail.image_config_detail';

    public function __construct(
        private readonly ImageThumbnailConfigDetail $thumbnailConfigDetail
    ) {
        parent::__construct($thumbnailConfigDetail);
    }

    public function getThumbnailConfigDetail(): ImageThumbnailConfigDetail
    {
        return $this->thumbnailConfigDetail;
    }
}
