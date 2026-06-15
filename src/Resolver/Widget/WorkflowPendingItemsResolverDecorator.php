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

namespace Pimcore\Bundle\StudioBackendBundle\Resolver\Widget;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Bundle\StudioDashboardsBundle\Schema\Widget\WidgetConfig;
use Pimcore\Workflow\Manager;
use Pimcore\Bundle\StudioDashboardsBundle\Schema\Widget\WorkflowPendingItemsWidgetConfig;
use Pimcore\Bundle\StudioDashboardsBundle\Resolver\Widget\DataResolverInterface;
use Pimcore\Bundle\StudioDashboardsBundle\Service\Loader\Widget\TaggedIteratorDataResolver;
use Pimcore\Bundle\StudioDashboardsBundle\Util\Constant\WidgetTypes;
use Pimcore\Model\Element\Service as ElementService;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @internal
 *
 * Enhanced version of WorkflowPendingItemsResolver with proper error handling and logging for state label resolution.
 */
#[AutoconfigureTag(TaggedIteratorDataResolver::RESOLVER_TAG, attributes: ['priority' => 10])]
final readonly class WorkflowPendingItemsResolverDecorator implements DataResolverInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
        private SecurityServiceInterface $securityService,
        private Manager $workflowManager,
    ) {
    }

    public function getSupportedWidgetType(): string
    {
        return WidgetTypes::WORKFLOW_PENDING_ITEMS->value;
    }

    /**
     * @throws DatabaseException
     *
     * @return array<int, array<string, mixed>>
     */
    public function resolveData(WidgetConfig $widget): array
    {
        if (!$widget instanceof WorkflowPendingItemsWidgetConfig) {
            return [];
        }

        if (empty($widget->getWorkflowName()) || empty($widget->getStateName())) {
            return [];
        }

        try {
            $db = $this->dbResolver->get();
            $qb = $db->createQueryBuilder()
                ->select('cid', 'ctype')
                ->from('element_workflow_state')
                ->where('workflow = :workflow')
                ->andWhere('FIND_IN_SET(:place, place) > 0')
                ->setParameter('workflow', $widget->getWorkflowName())
                ->setParameter('place', $widget->getStateName());

            if ($widget->getElementType() !== null) {
                $dbCtype = $widget->getElementType() === 'data-object' ? 'object' : $widget->getElementType();
                $qb->andWhere('ctype = :ctype')->setParameter('ctype', $dbCtype);
            }

            $qb->setMaxResults($widget->getLimit());
            $rows = $qb->fetchAllAssociative();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage());
        }

        $user = $this->securityService->getCurrentUser();
        $result = [];

        [$stateLabel, $stateColor] = $this->resolvePlaceInfo($widget);

        foreach ($rows as $row) {
            $ctype = $row['ctype'];
            $cid = (int)$row['cid'];

            // Convert DB ctype to Pimcore element type
            $elementType = $ctype === 'object' ? 'object' : $ctype;

            $element = ElementService::getElementById($elementType, $cid);
            if ($element === null) {
                continue;
            }

            try {
                $this->securityService->hasElementPermission(
                    $element,
                    $user,
                    ElementPermissions::VIEW_PERMISSION
                );
            } catch (ForbiddenException) {
                continue;
            }

            // Determine the display element type for the frontend
            $displayElementType = $ctype === 'object' ? 'data-object' : $ctype;

            $result[] = [
                'elementId' => $cid,
                'elementType' => $displayElementType,
                'path' => $element->getFullPath(),
                'objectKey' => $element->getKey(),
                'workflowName' => $widget->getWorkflowName(),
                'stateName' => $widget->getStateName(),
                'stateLabel' => $stateLabel,
                'stateColor' => $stateColor,
                'modificationDate' => $element->getModificationDate() ?? 0,
            ];
        }

        return $result;
    }

    /**
     * @return array{string, string} [stateLabel, stateColor]
     */
    private function resolvePlaceInfo(WorkflowPendingItemsWidgetConfig $widget): array
    {
        $stateName = $widget->getStateName();
        $workflowName = $widget->getWorkflowName();

        try {
            $placeConfig = $this->workflowManager->getPlaceConfig($workflowName, $stateName);

            if ($placeConfig === null) {
                return [$stateName, '#3572b0'];
            }

            return [$placeConfig->getLabel(), $placeConfig->getColor()];
        } catch (\Throwable) {
            return [$stateName, '#3572b0'];
        }
    }
}
