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

namespace Pimcore\Bundle\StudioBackendBundle\Response;

use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;

/**
 * Marks a response element that can carry the workflow states shown for it in listings like the tree.
 *
 * @internal
 */
interface WorkflowStatesAwareInterface
{
    public function getId(): int;

    public function getType(): string;

    public function getPermissions(): Permissions;

    /**
     * @return WorkflowStatus[]|null
     */
    public function getWorkflowStates(): ?array;

    /**
     * @param WorkflowStatus[] $workflowStates
     */
    public function setWorkflowStates(array $workflowStates): void;
}
