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

use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @internal
 */
final readonly class WorkflowDetailsService implements WorkflowDetailsServiceInterface
{
    public function __construct(
        private Manager $workflowManager,
        private WorkflowGraphServiceInterface $workflowGraphService,
    )
    {
    }

    public function getWorkflowLabel(WorkflowInterface $workflow): string
    {
        return $this->workflowManager->getWorkflowConfig($workflow->getName())->getLabel();
    }
    
    public function getStatusInfo(
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

    public function getGraph(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): string
    {
        $graphFile = $this->workflowGraphService->getGraphvizFile($workflow, $element);

        return $this->workflowGraphService->getGraphFromGraphvizFile($graphFile, 'svg');
    }
}