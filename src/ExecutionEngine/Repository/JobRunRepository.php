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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine\JobRunHidden;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;

/**
 * @internal
 */
final readonly class JobRunRepository implements JobRunRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function update(JobRunHidden $jobRunHidden): void
    {
        $existingEntry = $this->getByJobRunId($jobRunHidden->getJobRunId());
        if ($existingEntry !== null) {
            return;
        }

        $this->entityManager->persist($jobRunHidden);
        $this->entityManager->flush();
    }

    public function getByJobRunId(int $jobRunId): ?JobRunHidden
    {
        return $this->entityManager->getRepository(JobRunHidden::class)
            ->findOneBy(['jobRunId' => $jobRunId]);
    }

    public function getStudioJobRuns(int $ownerId, CollectionFilterParameter $parameter): Paginator
    {
        $qb = $this->entityManager
            ->getRepository(JobRun::class)
            ->createQueryBuilder('jr')
            ->leftJoin(JobRunHidden::class, 'jrh', 'WITH', 'jr.id = jrh.jobRunId')
            ->where('jrh.jobRunId IS NULL');

        // Add owner condition
        $qb->andWhere('jr.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId);

        $qb->setFirstResult($parameter->getFilters()->getStart());
        $qb->setMaxResults($parameter->getFilters()->getPageSize());

        $this->applyFilter($parameter, $qb);

        $qb->orderBy(
            'jr.'.$parameter->getFilters()->getSortFilter()->getKey(),
            $parameter->getFilters()->getSortFilter()->getDirection());

        // Add execution contexts condition
        $qb->andWhere('jr.executionContext IN (:executionContexts)')
            ->setParameter('executionContexts', ['studio_stop_on_error', 'studio_continue_on_error']);

        return new Paginator($qb, fetchJoinCollection: true);
    }

    private function applyFilter(CollectionFilterParameter $parameter, QueryBuilder $queryBuilder): void
    {
        if ($filter = $parameter->getFilters()->getSimpleColumnFilterByType('state')) {
            $queryBuilder->andWhere('jr.state = :state')->setParameter('state', $filter->getFilterValue());
        }

        if ($filter = $parameter->getFilters()->getSimpleColumnFilterByType('id')) {
            $queryBuilder->andWhere('jr.id = :id')->setParameter('id', $filter->getFilterValue());
        }
    }
}
