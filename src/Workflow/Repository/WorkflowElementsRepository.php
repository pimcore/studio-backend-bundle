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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;

/**
 * @internal
 */
final readonly class WorkflowElementsRepository implements WorkflowElementsRepositoryInterface
{
    public function __construct(
        private DbResolverInterface $dbResolver,
    ) {
    }

    public function fetchByWorkflowState(
        string $workflowName,
        ?string $stateName = null,
        ?string $elementType = null,
        ?int $page = null,
        ?int $pageSize = null,
    ): array {
        try {
            $qb = $this->dbResolver->get()->createQueryBuilder()
                ->select('ews.cid', 'ews.ctype')
                ->addSelect(
                    'COALESCE(a.modificationDate, o.modificationDate, d.modificationDate) AS modificationDate'
                )
                ->from('element_workflow_state', 'ews')
                ->leftJoin('ews', 'assets', 'a', "ews.ctype = 'asset' AND a.id = ews.cid")
                ->leftJoin('ews', 'objects', 'o', "ews.ctype = 'object' AND o.id = ews.cid")
                ->leftJoin(
                    'ews', 'documents', 'd', "ews.ctype = 'document' AND d.id = ews.cid"
                )
                ->where('ews.workflow = :workflow')
                ->setParameter('workflow', $workflowName)
                ->orderBy('modificationDate', 'ASC')
                ->addOrderBy('ews.cid', 'ASC')
                ->addOrderBy('ews.ctype', 'ASC');

            if ($pageSize !== null) {
                $qb->setFirstResult((($page ?? 1) - 1) * $pageSize)
                    ->setMaxResults($pageSize);
            }

            $this->applyWorkflowStateFilters($qb, $stateName, $elementType);

            return $qb->fetchAllAssociative();
        } catch (Exception $e) {
            throw new DatabaseException($e->getMessage(), $e);
        }
    }

    private function applyWorkflowStateFilters(QueryBuilder $qb, ?string $stateName, ?string $elementType): void
    {
        if ($stateName !== null && $stateName !== '') {
            $qb->andWhere('FIND_IN_SET(:place, ews.place) > 0')
                ->setParameter('place', $stateName);
        }

        if ($elementType !== null) {
            $ctype = $elementType === ElementTypes::TYPE_DATA_OBJECT ? ElementTypes::TYPE_OBJECT : $elementType;
            $qb->andWhere('ews.ctype = :ctype')->setParameter('ctype', $ctype);
        }
    }
}
