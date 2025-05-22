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

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\PageSnippet\Controller;
use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;

final class ControllerEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.document.list_available_controllers';

    public function __construct(
        private readonly Controller $controller
    ) {
        parent::__construct($controller);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getController(): Controller
    {
        return $this->controller;
    }
}
