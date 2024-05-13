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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Result;

/**
 * @internal
 */
final readonly class ActionSubmissionResult
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
