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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    title: 'WorkflowDetails',
    type: 'object'
)]
final readonly class WorkflowDetails
{
    public function __construct(
        #[Property(
            description: 'workflowName',
            type: 'string',
            example: 'Sample Asset Workflow'
        )]
        private string $workflowName,
        #[Property(
            description: 'workflowStatus',
            type: 'array',
            items: new Items(ref: WorkflowStatus::class)
        )]
        private array $workflowStatus,
        #[Property(
            description: 'graph',
            type: 'string',
            example: '<svg>...</svg>'
        )]
        private string $graph,
        #[Property(
            description: 'allowedTransitions',
            type: 'array',
            items: new Items(ref: AllowedTransition::class),
        )]
        private array $allowedTransitions,
        #[Property(
            description: 'globalActions',
            type: 'array',
            items: new Items(ref: GlobalAction::class)
        )]
        private array $globalActions,
    ) {

    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getWorkflowStatus(): array
    {
        return $this->workflowStatus;
    }

    public function getGraph(): string
    {
        return $this->graph;
    }

    public function getAllowedTransitions(): array
    {
        return $this->allowedTransitions;
    }

    public function getGlobalActions(): array
    {
        return $this->globalActions;
    }
}