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

use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Event\PreResponse\WorkflowDetailsEvent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowDetailsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowDetails;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use Pimcore\Workflow\Manager;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;

/**
 * @internal
 */
final readonly class WorkflowDetailsService implements WorkflowDetailsServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Manager $workflowManager,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowDetailsHydratorInterface $hydrator,
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
            $workflowDetails = $this->hydrator->hydrate(
                $element,
                $workflow
            );

            $this->eventDispatcher->dispatch(
                new WorkflowDetailsEvent($workflowDetails),
                WorkflowDetailsEvent::EVENT_NAME
            );

            $details[] = $workflowDetails;
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
}
