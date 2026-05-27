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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\PreviewConfigEntry;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class PreviewConfigEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.data_object.preview_config_entry';

    public function __construct(
        private readonly PreviewConfigEntry $previewConfigEntry,
    ) {
        parent::__construct($this->previewConfigEntry);
    }

    public function getPreviewConfigEntry(): PreviewConfigEntry
    {
        return $this->previewConfigEntry;
    }
}
