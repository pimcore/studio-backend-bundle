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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\FolderPreviewSetting;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class FolderPreviewSettingEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.asset.folder_preview_setting';

    public function __construct(
        private readonly FolderPreviewSetting $folderPreviewSetting
    ) {
        parent::__construct($folderPreviewSetting);
    }

    public function getFolderPreviewSetting(): FolderPreviewSetting
    {
        return $this->folderPreviewSetting;
    }
}
