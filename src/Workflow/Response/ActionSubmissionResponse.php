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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Response;

/**
 * @internal
 */
final readonly class ActionSubmissionResponse
{
    public function __construct(
        private string $workflowName,
        private string $actionName,
        private string $actionType
    ) {
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getActionName(): string
    {
        return $this->actionName;
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }
}
