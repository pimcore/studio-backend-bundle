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
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine\JobRunHidden;

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

    public function getStudioJobRuns(int $ownerId): array
    {
        $qb = $this->entityManager
            ->getRepository(JobRun::class)
            ->createQueryBuilder('jr')
            ->leftJoin(JobRunHidden::class, 'jrh', 'WITH', 'jr.id = jrh.jobRunId')
            ->where('jrh.jobRunId IS NULL');

        // Add owner condition
        $qb->andWhere('jr.ownerId = :ownerId')
            ->setParameter('ownerId', $ownerId);

        // Add execution contexts condition
        $qb->andWhere('jr.executionContext IN (:executionContexts)')
            ->setParameter('executionContexts', ['studio_stop_on_error', 'studio_continue_on_error']);

        return $qb->getQuery()->getResult();
    }
}
