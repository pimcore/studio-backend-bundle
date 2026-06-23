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

namespace Pimcore\Bundle\StudioBackendBundle\Schema\WidgetData;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 *
 * This schema extends the vendor WorkflowPendingItem to ensure stateLabel is marked as required
 * in the OpenAPI documentation, ensuring it's always included in API responses.
 */
#[Schema(
    title: 'WorkflowPendingItem',
    required: ['elementId', 'elementType', 'path', 'workflowName', 'stateName', 'stateLabel', 'stateColor', 'modificationDate'],
    type: 'object'
)]
final readonly class WorkflowPendingItem
{
    public function __construct(
        #[Property(description: 'Element ID', type: 'integer', example: 123)]
        private int $elementId,
        #[Property(description: 'Element type', type: 'string', example: 'data-object')]
        private string $elementType,
        #[Property(description: 'Full path', type: 'string', example: '/products/my-product')]
        private string $path,
        #[Property(description: 'Workflow name', type: 'string', example: 'product_workflow')]
        private string $workflowName,
        #[Property(description: 'Current state name', type: 'string', example: 'in_review')]
        private string $stateName,
        #[Property(description: 'Human-readable state label', type: 'string', example: 'In Review')]
        private string $stateLabel,
        #[Property(description: 'State color', type: 'string', example: '#3572b0')]
        private string $stateColor,
        #[Property(description: 'Modification timestamp', type: 'integer', example: 1704067200)]
        private int $modificationDate,
    ) {
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getStateName(): string
    {
        return $this->stateName;
    }

    public function getStateLabel(): string
    {
        return $this->stateLabel;
    }

    public function getStateColor(): string
    {
        return $this->stateColor;
    }

    public function getModificationDate(): int
    {
        return $this->modificationDate;
    }
}
