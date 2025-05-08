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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Schema;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'WorkflowDetails',
    required: [
        'workflowName',
        'workflowLabel',
        'workflowStatus',
        'graph',
        'workflowLayoutId',
        'allowedTransitions',
        'globalActions'
    ],
    type: 'object'
)]
final class WorkflowDetails implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(
            description: 'workflowName',
            type: 'string',
            example: 'simple_asset'
        )]
        private readonly string $workflowName,
        #[Property(
            description: 'workflowLabel',
            type: 'string',
            example: 'Sample Asset Workflow'
        )]
        private readonly string $workflowLabel,
        #[Property(
            description: 'workflowStatus',
            type: 'array',
            items: new Items(ref: WorkflowStatus::class)
        )]
        private readonly array $workflowStatus,
        #[Property(
            description: 'graph',
            type: 'string',
            example: '<svg>...</svg>'
        )]
        private readonly string $graph,
        #[Property(
            description: 'workflowLayoutId',
            type: 'string',
            example: 'someWorkflowLayoutId'
        )]
        private readonly ?string $workflowLayoutId,
        #[Property(
            description: 'allowedTransitions',
            type: 'array',
            items: new Items(ref: AllowedTransition::class),
        )]
        private readonly array $allowedTransitions,
        #[Property(
            description: 'globalActions',
            type: 'array',
            items: new Items(ref: GlobalAction::class)
        )]
        private readonly array $globalActions,
    ) {

    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getWorkflowLabel(): string
    {
        return $this->workflowLabel;
    }

    public function getWorkflowStatus(): array
    {
        return $this->workflowStatus;
    }

    public function getGraph(): string
    {
        return $this->graph;
    }

    public function getLayoutId(): ?string
    {
        return $this->workflowLayoutId;
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
