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

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service\ExecutionEngine;

use Generator;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\ExecutionEngine\AutomationAction\Messenger\Messages\BatchTagOperationMessage;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchOperationParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Util\Constant\BatchOperations;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use function sprintf;

/**
 * @internal
 */
final readonly class BatchService implements BatchServiceInterface
{
    use ElementProviderTrait;

    private const int BATCH_TAG_SIZE = 500;

    public function __construct(
        private ElementSearchServiceInterface $elementSearchService,
        private ElementServiceInterface $elementService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private TagServiceInterface $tagService
    ) {
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    public function createJobRunForBatchOperation(BatchOperationParameters $parameters): int
    {
        $user = $this->securityService->getCurrentUser();
        $parent = $this->elementService->getAllowedElementById($parameters->getType(), $parameters->getId(), $user);
        $childrenIds = $this->elementSearchService->getChildrenIds($parameters->getType(), $parent->getRealFullPath());
        if (empty($childrenIds)) {
            throw new NotFoundException(
                sprintf('Children for %s', $parameters->getType()),
                $parameters->getId()
            );
        }
        $tagIds = $this->tagService->getTagIdsForElement(
            new ElementParameters($parameters->getType(), $parameters->getId())
        );

        $jobSteps = [];
        foreach ($this->chunkGenerator($childrenIds, self::BATCH_TAG_SIZE) as $batch) {
            $jobSteps[] = new JobStep(
                $this->getJobStepName($parameters->getOperation()),
                BatchTagOperationMessage::class,
                '',
                [
                    StepConfig::ELEMENTS_TO_TAG->value => $batch,
                    StepConfig::ELEMENT_TYPE_TO_TAG->value => $parameters->getType(),
                ],
            );
        }

        $job = new Job(
            name: $this->getJobName($parameters->getOperation()),
            steps: $jobSteps,
            environmentData: [
                EnvironmentVariables::BATCH_TAG_OPERATION->value => $parameters->getOperation(),
                EnvironmentVariables::TAG_IDS->value => $tagIds,
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function getJobName(string $operation): string
    {
        return $operation === BatchOperations::ASSIGN->value ?
            Jobs::BATCH_TAG_ASSIGN->value :
            Jobs::BATCH_TAG_REPLACE->value;
    }

    private function getJobStepName(string $operation): string
    {
        return $operation === BatchOperations::ASSIGN->value ?
            JobSteps::ELEMENT_BATCH_TAG_ASSIGN->value :
            JobSteps::ELEMENT_BATCH_TAG_REPLACE->value;
    }

    // TODO: replace with ChunkGeneratorTrait once https://github.com/pimcore/studio-backend-bundle/pull/1800 is merged
    private function chunkGenerator(array $items, int $size): Generator
    {
        $total = count($items);

        for ($i = 0; $i < $total; $i += $size) {
            yield array_slice($items, $i, $size);
        }
    }
}
