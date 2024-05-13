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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\AllowedTransitionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\GlobalActionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Request\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowDetailsService implements WorkflowDetailsServiceInterface
{
    use ElementProviderTrait;
    
    public function __construct(
        private AllowedTransitionsHydratorInterface $allowedTransitionsHydrator,
        private GlobalActionsHydratorInterface $globalActionsHydrator,
        private Manager $workflowManager,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowActionServiceInterface $workflowActionService,
        private WorkflowGraphServiceInterface $workflowGraphService,
    )
    {
    }

    /**
     * @return WorkflowDetails[]
     */
    public function hydrateWorkflowDetails(
        WorkflowDetailsParameters $parameters,
        UserInterface $user
    ): array
    {
        $element = $this->getElement(
            $this->serviceResolver,
            $parameters->getElementType(),
            $parameters->getElementId(),
        );

        $this->securityService->hasElementPermission(
            $element,
            $user,
            ElementPermissions::VIEW_PERMISSION
        );

        $details =  [];
        $elementWorkflows = $this->workflowManager->getAllWorkflowsForSubject($element);
        foreach ($elementWorkflows as $workflow) {
            $details[] = $this->hydrate(
                $element,
                $workflow
            );
        }

        return $details;
    }
    
    private function hydrate(
        ElementInterface $element,
        WorkflowInterface $workflow
    ): WorkflowDetails {
        return new WorkflowDetails(
            $this->getWorkflowLabel($workflow),
            $this->getStatusInfo($workflow, $element),
            $this->getGraph($workflow, $element),
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


    private function getWorkflowLabel(WorkflowInterface $workflow): string
    {
        return $this->workflowManager->getWorkflowConfig($workflow->getName())->getLabel();
    }
    
    private function getStatusInfo(
        WorkflowInterface $workflow,
        ElementInterface $element,
    ): array
    {
        $marking = $workflow->getMarking($element);
        $statuses = $this->workflowManager->getOrderedPlaceConfigs($workflow, $marking);
        $uniqueStatuses = [];
        $statusInfos = [];

        foreach ($statuses as $status) {
            $uniqueStatuses[$status->getPlace()] = $status;
        }

        foreach ($uniqueStatuses as $status) {
            $statusInfos[] = new WorkflowStatus(
                $status->getBackgroundColor(),
                $status->getFontColor(),
                $status->getBorderColor(),
                $status->getPlace(),
                $status->getLabel(),
            );
        }

        return $statusInfos;
    }

    private function getGraph(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): string
    {
        $graphFile = $this->workflowGraphService->getGraphvizFile($workflow, $element);

        return $this->workflowGraphService->getGraphFromGraphvizFile(
            $graphFile,
            'svg'
        );
    }
}