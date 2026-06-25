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

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Util\Schema\AdditionalAttributesInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\AdditionalAttributesTrait;

/**
 * @internal
 */
#[Schema(
    title: 'WorkflowElement',
    required: [
        'elementId',
        'elementType',
        'path',
        'objectKey',
        'workflowName',
        'stateName',
        'stateLabel',
        'stateColor',
        'modificationDate',
    ],
    type: 'object'
)]
final class WorkflowElement implements AdditionalAttributesInterface
{
    use AdditionalAttributesTrait;

    public function __construct(
        #[Property(description: 'Element ID', type: 'integer', example: 123)]
        private readonly int $elementId,
        #[Property(description: 'Element type', type: 'string', example: 'data-object')]
        private readonly string $elementType,
        #[Property(description: 'Full path', type: 'string', example: '/products/my-product')]
        private readonly string $path,
        #[Property(description: 'Element key', type: 'string', example: 'my-product')]
        private readonly string $objectKey,
        #[Property(description: 'Workflow name', type: 'string', example: 'product_workflow')]
        private readonly string $workflowName,
        #[Property(description: 'Current state name', type: 'string', example: 'in_review')]
        private readonly string $stateName,
        #[Property(description: 'Human-readable state label', type: 'string', example: 'In Review')]
        private readonly string $stateLabel,
        #[Property(description: 'State color', type: 'string', example: '#3572b0')]
        private readonly string $stateColor,
        #[Property(description: 'Modification timestamp', type: 'integer', example: 1704067200)]
        private readonly int $modificationDate,
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

    public function getObjectKey(): string
    {
        return $this->objectKey;
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
