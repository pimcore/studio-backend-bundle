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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;

/**
 * @internal
 */
trait WorkflowStatesTrait
{
    #[Property(
        description: 'Workflow states in the header; '
            . 'null outside tree listings, for unviewable elements and asset folders',
        type: 'array',
        items: new Items(ref: WorkflowStatus::class),
        nullable: true
    )]
    private ?array $workflowStates = null;

    /**
     * @return WorkflowStatus[]|null
     */
    public function getWorkflowStates(): ?array
    {
        return $this->workflowStates;
    }

    /**
     * @param WorkflowStatus[] $workflowStates
     */
    public function setWorkflowStates(array $workflowStates): void
    {
        $this->workflowStates = $workflowStates;
    }
}
