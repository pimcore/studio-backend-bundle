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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\AssetSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DataObjectSearchPreview;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DocumentSearchPreview;

final class SimpleSearchPreviewEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.simple_search.preview';

    public function __construct(
        private readonly AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview $preview
    ) {
        parent::__construct($this->preview);
    }

    public function getSimpleSearchResult(): AssetSearchPreview|DataObjectSearchPreview|DocumentSearchPreview
    {
        return $this->preview;
    }
}
