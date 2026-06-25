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
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Event\PreResponse\WorkflowElementEvent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowElementsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowElementsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Pimcore\Model\User;
use Pimcore\Workflow\Manager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;
use function array_slice;
use function count;

/**
 * @internal
 */
final readonly class WorkflowElementsService implements WorkflowElementsServiceInterface
{
    private const string DEFAULT_STATE_COLOR = '#3572b0';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private Manager $workflowManager,
        private SecurityServiceInterface $securityService,
        private ServiceResolverInterface $serviceResolver,
        private WorkflowElementsHydratorInterface $hydrator,
        private WorkflowElementsRepositoryInterface $elementsRepository,
    ) {
    }

    public function getElements(WorkflowElementsParameters $parameters): Collection
    {
        $workflowName = $parameters->getWorkflowName();
        if ($workflowName === '') {
            return new Collection(0, []);
        }

        $user = $this->securityService->getCurrentUser();
        if (!$user instanceof User) {
            return new Collection(0, []);
        }

        $stateName = $parameters->getStateName();
        $rows = $this->elementsRepository->fetchByWorkflowState(
            $workflowName,
            $stateName,
            $parameters->getElementType(),
        );

        [$stateLabel, $stateColor] = $stateName !== null && $stateName !== ''
            ? $this->resolvePlaceInfo($workflowName, $stateName)
            : ['', self::DEFAULT_STATE_COLOR];

        $viewable = [];
        foreach ($rows as $row) {
            $element = $this->serviceResolver->getElementById((string) $row['ctype'], (int) $row['cid']);

            if ($element === null || !$element->isAllowed(ElementPermissions::VIEW_PERMISSION, $user)) {
                continue;
            }

            $viewable[] = [$element, $row];
        }

        $total = count($viewable);
        $pageSize = $parameters->getPageSize();
        $pageRows = array_slice($viewable, ($parameters->getPage() - 1) * $pageSize, $pageSize);

        $items = [];
        foreach ($pageRows as [$element, $row]) {
            $workflowElement = $this->hydrator->hydrate(
                $element,
                $row,
                $workflowName,
                $stateName ?? '',
                $stateLabel,
                $stateColor,
            );
            $this->eventDispatcher->dispatch(
                new WorkflowElementEvent($workflowElement),
                WorkflowElementEvent::EVENT_NAME
            );

            $items[] = $workflowElement;
        }

        return new Collection($total, $items);
    }

    /**
     * @return array{string, string} [stateLabel, stateColor]
     */
    private function resolvePlaceInfo(string $workflowName, string $stateName): array
    {
        try {
            $placeConfig = $this->workflowManager->getPlaceConfig($workflowName, $stateName);

            if ($placeConfig === null) {
                return [$stateName, self::DEFAULT_STATE_COLOR];
            }

            return [$placeConfig->getLabel(), $placeConfig->getColor()];
        } catch (Throwable) {
            return [$stateName, self::DEFAULT_STATE_COLOR];
        }
    }
}
