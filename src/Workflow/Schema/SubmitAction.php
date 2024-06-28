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

use function in_array;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidActionTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\WorkflowActionTypes;

/**
 * @internal
 */
#[Schema(
    title: 'SubmitAction',
    description: 'Schema for submission of workflow action',
    required: ['actionType', 'elementId', 'elementType', 'workflowName', 'transition', 'workflowOptions'],
    type: 'object'
)]
final readonly class SubmitAction
{
    public function __construct(
        #[Property(
            description: 'type of the action',
            type: 'string',
            example: WorkflowActionTypes::TRANSITION_ACTION
        )]
        private string $actionType,
        #[Property(description: 'id of the element', type: 'integer', example: 50)]
        private int $elementId,
        #[Property(description: 'type of the element', type: 'string', example: ElementTypes::TYPE_OBJECT)]
        private string $elementType,
        #[Property(description: 'name of the workflow', type: 'string', example: 'my_first_workflow')]
        private string $workflowName,
        #[Property(description: 'transition', type: 'string', example: 'start_workflow')]
        private string $transition,
        #[Property(description: 'workflowOptions', type: 'array', items: new Items(), example: [])]
        private array $workflowOptions = [],
    ) {
        if (!in_array($this->actionType, WorkflowActionTypes::ALLOWED_TYPES, true)) {
            throw new InvalidActionTypeException($this->actionType);
        }

        if (!in_array($this->elementType, ElementTypes::ALLOWED_TYPES, true)) {
            throw new InvalidElementTypeException($this->elementType);
        }
    }

    public function getActionType(): string
    {
        return $this->actionType;
    }

    public function getElementId(): int
    {
        return $this->elementId;
    }

    public function getElementType(): string
    {
        return $this->elementType;
    }

    public function getWorkflowName(): string
    {
        return $this->workflowName;
    }

    public function getWorkflowOptions(): array
    {
        return $this->workflowOptions;
    }

    public function getTransition(): string
    {
        return $this->transition;
    }
}
