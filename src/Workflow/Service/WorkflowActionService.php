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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidActionTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final readonly class WorkflowActionService implements WorkflowActionServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private Manager $workflowManager,
        private Registry $workflowRegistry,
        private SecurityServiceInterface $securityService,
        private ServiceProviderInterface $actionSubmitterLocator,
        private ServiceResolverInterface $serviceResolver,
    ) {
    }

    public function submitAction(
        UserInterface $user,
        SubmitAction $parameters
    ): ActionSubmissionResponse {
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

        try {
            $workflow = $this->workflowRegistry->get(
                $element,
                $parameters->getWorkflowName()
            );
        } catch (Exception $exception) {
            throw new WorkflowActionSubmissionException(
                $parameters->getTransition(),
                $exception->getMessage(),
            );
        }

        $actionType = $parameters->getActionType();
        if (!$this->actionSubmitterLocator->has($actionType)) {
            throw new InvalidActionTypeException($actionType);
        }

        return $this->actionSubmitterLocator->get($actionType)->submit(
            $element,
            $workflow,
            $parameters
        );
    }

    public function enrichActionNotes(
        Concrete|Folder $object,
        array $notes
    ): array {
        if (empty($notes)) {
            return $notes;
        }

        $notes['commentPrefill'] = '';
        if (!empty($notes['commentGetterFn'])) {
            $commentGetterFn = $notes['commentGetterFn'];
            $notes['commentPrefill'] = $object->$commentGetterFn();
        }

        return $notes;
    }

    /**
     * @return GlobalAction[]
     */
    public function getGlobalActions(
        WorkflowInterface $workflow,
        ElementInterface $element
    ): array {
        $globalActions = [];
        $allGlobalActions = $this->workflowManager->getGlobalActions($workflow->getName());
        foreach ($allGlobalActions as $globalAction) {
            if (!$globalAction->isGuardValid($workflow, $element)) {
                continue;
            }

            $globalActions[] = $globalAction;
        }

        return $globalActions;
    }
}
