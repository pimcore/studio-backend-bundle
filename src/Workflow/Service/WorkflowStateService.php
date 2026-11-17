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
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowStatusHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\Manager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\WorkflowInterface;
use function array_fill_keys;
use function sprintf;

/**
 * @internal
 */
final readonly class WorkflowStateService implements WorkflowStateServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private Manager $workflowManager,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowStatusHydratorInterface $workflowStatusHydrator,
        private LoggerInterface $logger,
    ) {
    }

    public function applyVisibleStates(iterable $elements, string $elementType): void
    {
        $hasWorkflows = $this->workflowManager->getAllWorkflows() !== [];
        $coreElementType = $this->getCoreElementType($elementType);

        foreach ($elements as $element) {
            if (!$element instanceof WorkflowStatesAwareInterface) {
                continue;
            }

            if (!$this->showsWorkflowStates($element, $elementType)) {
                continue;
            }

            $subject = $hasWorkflows
                ? $this->serviceResolver->getElementById($coreElementType, $element->getId())
                : null;
            $element->setWorkflowStates($subject === null ? [] : $this->getVisibleStates($subject));
        }
    }

    // like the editor: workflow details need the view permission, and asset folders never show workflows
    private function showsWorkflowStates(WorkflowStatesAwareInterface $element, string $elementType): bool
    {
        if (!$element->getPermissions()->isView()) {
            return false;
        }

        return $elementType !== ElementTypes::TYPE_ASSET || $element->getType() !== ElementTypes::TYPE_FOLDER;
    }

    /**
     * @return WorkflowStatus[]
     */
    private function getVisibleStates(ElementInterface $subject): array
    {
        $states = [];

        // support strategies and marking stores are project code: one failing element must not break the listing
        try {
            foreach ($this->workflowManager->getAllWorkflowsForSubject($subject) as $workflow) {
                $marking = $this->getMarking($workflow, $subject);
                foreach ($this->workflowManager->getOrderedPlaceConfigs($workflow, $marking) as $placeConfig) {
                    if ($placeConfig->isVisibleInHeader()) {
                        $states[] = $this->workflowStatusHydrator->hydrate($placeConfig);
                    }
                }
            }
        } catch (Exception $exception) {
            $this->logger->warning(
                sprintf(
                    'Could not resolve the workflow states of element %d: %s',
                    $subject->getId(),
                    $exception->getMessage()
                ),
                ['exception' => $exception]
            );

            return [];
        }

        return $states;
    }

    // WorkflowInterface::getMarking() would store the initial places, a tree request must not write
    private function getMarking(WorkflowInterface $workflow, ElementInterface $subject): Marking
    {
        $marking = $workflow->getMarkingStore()->getMarking($subject);
        if ($marking->getPlaces() !== []) {
            return $marking;
        }

        return new Marking(array_fill_keys($workflow->getDefinition()->getInitialPlaces(), 1));
    }
}
