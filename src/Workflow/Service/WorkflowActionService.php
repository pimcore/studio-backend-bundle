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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidActionTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\WorkflowActionSubmissionException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\SimpleUser;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Response\ActionSubmissionResponse;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\GlobalAction;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Notes\CustomHtmlServiceInterface;
use Pimcore\Workflow\Transition;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * @internal
 */
final class WorkflowActionService implements WorkflowActionServiceInterface
{
    use ElementProviderTrait;

    private array $usersCache = [];

    public function __construct(
        private readonly Manager $workflowManager,
        private readonly Registry $workflowRegistry,
        private readonly SecurityServiceInterface $securityService,
        private readonly ServiceProviderInterface $actionSubmitterLocator,
        private readonly ServiceResolverInterface $serviceResolver,
        private readonly UserServiceInterface $userService,
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
        GlobalAction|Transition $action,
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

        if (isset($notes['customHtml'])) {
            $notes['customHtml'] = $this->enrichCustomHtmlField(
                $object,
                $notes['customHtml'],
                $action->getCustomHtmlService()
            );
        }

        if (isset($notes['additionalFields'])) {
            $notes['additionalFields'] = $this->enrichNoteAdditionalFields($notes['additionalFields']);
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

    private function enrichCustomHtmlField(
        ElementInterface $element,
        array $customHtml,
        ?CustomHtmlServiceInterface $customHtmlService
    ): array {
        $customHtml['values'] = [];
        if ($customHtmlService === null) {
            return $customHtml;
        }

        foreach (['top', 'center', 'bottom'] as $position) {
            $customHtml['values'][$position] = $customHtmlService->renderHtmlForRequestedPosition($element, $position);
        }

        return $customHtml;
    }

    private function enrichNoteAdditionalFields(array $additionalFields): array
    {
        if (empty($additionalFields)) {
            return $additionalFields;
        }

        foreach ($additionalFields as $index => $additionalField) {

            if (isset($additionalField['fieldTypeSettings']['fieldtype']) &&
                $additionalField['fieldTypeSettings']['fieldtype'] === 'user'
            ) {
                $additionalFields[$index]['fieldTypeSettings']['options'] = $this->getUserOptions();
            }
        }

        return $additionalFields;
    }

    private function getUserOptions(): array
    {
        if (!empty($this->usersCache)) {
            return $this->usersCache;
        }

        /** @var SimpleUser $user */
        foreach ($this->userService->getUsers()->getItems() as $user) {
            $this->usersCache[] = [
                'key' => $user->getUsername(),
                'value' => $user->getId(),
            ];
        }

        return $this->usersCache;
    }
}
