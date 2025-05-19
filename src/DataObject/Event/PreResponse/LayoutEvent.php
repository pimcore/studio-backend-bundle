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

use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\Layout;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class LayoutEvent extends AbstractPreResponseEvent
{
    public const EVENT_NAME = 'pre_response.data_object.layout';

    public function __construct(
        private readonly Layout $layout
    ) {
        parent::__construct($this->layout);
    }

    /**
     * Use this to get additional infos out of the response object
     */
    public function getLayout(): Layout
    {
        return $this->layout;
    }
}
