<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
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
