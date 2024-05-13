<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under following license:
 * - Pimcore Commercial License (PCL)
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowDetailsHydrator implements WorkflowDetailsHydratorInterface
{
    public function __construct(
        private WorkflowActionServiceInterface $workflowActionService,
        private AllowedTransitionsHydratorInterface $allowedTransitionsHydrator,
        private GlobalActionsHydratorInterface $globalActionsHydrator,
        private WorkflowDetailsServiceInterface $workflowDetailsService,
    )
    {
    }
    
    public function hydrate(
        ElementInterface $element,
        WorkflowInterface $workflow
    ): WorkflowDetails {
        return new WorkflowDetails(
            $this->workflowDetailsService->getWorkflowLabel($workflow),
            $this->workflowDetailsService->getStatusInfo($workflow, $element),
            $this->workflowDetailsService->getGraph($workflow, $element),
            $this->allowedTransitionsHydrator->hydrate(
                $workflow->getEnabledTransitions($element),
                $element
            ),
            $this->globalActionsHydrator->hydrate(
               $this->workflowActionService->getGlobalActions($workflow, $element),
                $element
            ),
        );
    }
}