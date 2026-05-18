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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\RenderAreaBlockData;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class RenderBlockEditmodeEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.document.page-snippet.render-area-block-editmode';

    public function __construct(
        private readonly RenderAreaBlockData $areaBlockData
    ) {
        parent::__construct($areaBlockData);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getAreaBlockData(): RenderAreaBlockData
    {
        return $this->areaBlockData;
    }
}
