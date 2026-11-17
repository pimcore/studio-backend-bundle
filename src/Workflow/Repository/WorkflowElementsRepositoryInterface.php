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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Repository;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;

/**
 * @internal
 */
interface WorkflowElementsRepositoryInterface
{
    /**
     * Fetch candidate rows from element_workflow_state, optionally filtered by state and element type.
     * Rows are ordered oldest-first. When $pageSize is null the full candidate set is returned so
     * callers can perform permission filtering before paginating in PHP; when only $pageSize is
     * given the offset defaults to the first page.
     *
     * @throws DatabaseException
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchByWorkflowState(
        string $workflowName,
        ?string $stateName = null,
        ?string $elementType = null,
        ?int $page = null,
        ?int $pageSize = null,
    ): array;
}
