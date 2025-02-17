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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\AllowedTransitionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\GlobalActionsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Workflow\WorkflowInterface;
use function count;

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
    ) {
    }

    /**
     * @return WorkflowDetails[]
     */
    public function getWorkflowDetails(
        WorkflowDetailsParameters $parameters,
        UserInterface $user
    ): array {
        $element = $this->getUserElement($parameters->getElementId(), $parameters->getElementType(), $user);
        $element->setUserModification($user->getId());

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

    public function hasElementWorkflowsById(int $elementId, string $elementType, UserInterface $user): bool
    {
        return $this->hasElementWorkflows(
            $this->getUserElement($elementId, $elementType, $user)
        );
    }

    public function hasElementWorkflows(ElementInterface $element): bool
    {
        try {
            return count($this->workflowManager->getAllWorkflowsForSubject($element)) > 0;
        } catch (SyntaxError) {
            return false;
        }
    }

    /**
     * @throws NotFoundException
     */
    private function getUserElement(int $elementId, string $elementType, UserInterface $user): ElementInterface
    {
        $element = $this->getElement(
            $this->serviceResolver,
            $elementType,
            $elementId,
        );
        $version = $this->getLatestVersionForUser($element, $user);

        return $this->getVersionData($element, $version);
    }

    private function hydrate(
        ElementInterface $element,
        WorkflowInterface $workflow
    ): WorkflowDetails {
        return new WorkflowDetails(
            $this->getWorkflowLabel($workflow),
            $this->getStatusInfo($workflow, $element),
            $this->workflowGraphService->getGraph(
                $element,
                $workflow,
                'svg'
            ),
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
    ): array {
        $marking = $workflow->getMarking($element);
        $statuses = $this->workflowManager->getOrderedPlaceConfigs($workflow, $marking);
        $uniqueStatuses = [];
        $statusInfos = [];

        foreach ($statuses as $status) {
            $uniqueStatuses[$status->getPlace()] = $status;
        }

        foreach ($uniqueStatuses as $status) {
            $statusInfos[] = new WorkflowStatus(
                $status->getColor(),
                $status->getColorInverted(),
                $status->getPlace(),
                $status->getLabel(),
                $status->isVisibleInHeader(),
            );
        }

        return $statusInfos;
    }
}
