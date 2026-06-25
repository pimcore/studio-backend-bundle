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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Event\PreResponse;

use Pimcore\Bundle\StudioBackendBundle\Event\AbstractPreResponseEvent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowElement;

final class WorkflowElementEvent extends AbstractPreResponseEvent
{
    public const string EVENT_NAME = 'pre_response.workflow_element';

    public function __construct(
        private readonly WorkflowElement $workflowElement
    ) {
        parent::__construct($this->workflowElement);
    }

    /**
     * Use this to get additional info out of the response object
     */
    public function getWorkflowElement(): WorkflowElement
    {
        return $this->workflowElement;
    }
}
