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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow;

use Pimcore\Bundle\AdminBundle\Service\Workflow\ActionsButtonService;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Dumper\GraphvizDumper;
use Pimcore\Workflow\Dumper\StateMachineGraphvizDumper;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowDetailsService implements WorkflowDetailsServiceInterface
{
    public function __construct(
        private ActionsButtonService $actionsButtonService,
        private Manager $workflowManager,
        private GraphvizDumper $graphvizDumper,
        private StateMachineGraphvizDumper $stateMachineGraphvizDumper
    )
    {
    }

    public function getWorkflowLabel(WorkflowInterface $workflow): string
    {
        return $this->workflowManager->getWorkflowConfig($workflow->getName())->getLabel();
    }
    
    public function getStatusInfo(
        ElementInterface $element,
        WorkflowInterface $workflow,
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

    public function getGraph(WorkflowInterface $workflow): string
    {
        $bla = new \Symfony\Component\Workflow\Dumper\GraphvizDumper();
        $marking = new Marking();
        $dumper = $this->graphvizDumper;
        $configuration = $this->workflowManager->getWorkflowConfig($workflow->getName());
        if ($configuration->getType() === 'state_machine') {
            $dumper = $this->stateMachineGraphvizDumper;
        }
        foreach ($workflow->getDefinition()->getPlaces() as $place) {
            $marking->mark($place);
        }
        return $dumper->dump($workflow->getDefinition(), $marking, ['workflowName' => $workflow->getName()]);
    }

    public function getAllowedTransitions(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): array
    {
        return $this->actionsButtonService->getAllowedTransitions($workflow, $element);
    }

    public function getGlobalActions(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): array
    {
        return $this->actionsButtonService->getGlobalActions($workflow, $element);
    }

}