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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service;

use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\Entity\ExecutionEngine\JobRunHidden;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Event\PreResponse\JobRunListEvent;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Hydrator\JobRunListHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\MappedParameter\HideJobRunsParameter;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Exception\NotFoundException as CoreNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class ExecutionEngineService implements ExecutionEngineServiceInterface
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private JobRunListHydratorInterface $jobRunHydrator,
        private JobRunRepositoryInterface $jobRunRepository,
        private SecurityServiceInterface $securityService,
    ) {

    }

    /**
     * {@inheritdoc}
     */
    public function listJobRuns(): array
    {
        $jobs = $this->jobRunRepository->getStudioJobRuns($this->securityService->getCurrentUser()->getId());
        $hydratedJobs = [];

        foreach ($jobs as $job) {
            $hydrated = $this->jobRunHydrator->hydrate($job);
            $this->eventDispatcher->dispatch(new JobRunListEvent($hydrated), JobRunListEvent::EVENT_NAME);
            $hydratedJobs[] = $hydrated;
        }

        return $hydratedJobs;
    }

    /**
     * {@inheritdoc}
     */
    public function abortAction(
        int $jobRunId,
    ): void {
        $this->validateJobRun($jobRunId);

        try {
            $this->jobExecutionAgent->cancelJobRun($jobRunId);
        } catch (Exception $e) {
            throw new DatabaseException(
                sprintf(
                    'Failed to abort job run: %s',
                    $e->getMessage()
                )
            );
        }

        $this->hideJobRun($jobRunId);
    }

    public function hideAction(HideJobRunsParameter $parameter): void
    {
        $jobRunIds = $parameter->getJobRunIds();
        foreach ($jobRunIds as $jobRunId) {
            $this->validateJobRun($jobRunId);
            $this->hideJobRun($jobRunId);
        }
    }

    public function hideJobRun(int $jobRunId): void
    {
        $jobRunHidden = new JobRunHidden();
        $jobRunHidden->setJobRunId($jobRunId);
        $this->jobRunRepository->update($jobRunHidden);
    }

    /**
     * {@inheritdoc}
     */
    public function validateJobRun(int $jobRunId): void
    {
        try {
            $allowed = $this->jobExecutionAgent->isInteractionAllowed(
                $jobRunId,
                $this->securityService->getCurrentUser()->getId()
            );
        } catch (CoreNotFoundException) {
            throw new NotFoundException('JobRun', $jobRunId);
        }

        if (!$allowed) {
            throw new ForbiddenException('Only job owner can access the resource.');
        }
    }
}
