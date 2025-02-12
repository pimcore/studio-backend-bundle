<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\ElementSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\ExecutionEngine\AutomationAction\Messenger\Messages\BatchTagOperationMessage;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchOperationParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\TagServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tag\Util\Constant\BatchOperations;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
use Pimcore\Model\Element\ElementDescriptor;
use function sprintf;

/**
 * @internal
 */
final readonly class BatchService implements BatchServiceInterface
{
    use ElementProviderTrait;

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
        if (empty($tagIds)) {
            throw new NotFoundException(
                sprintf('Tags for %s', $parameters->getType()),
                $parameters->getId()
            );
        }

        $job = new Job(
            name: $this->getJobName($parameters->getOperation()),
            steps: [
                new JobStep(
                    $this->getJobStepName($parameters->getOperation()),
                    BatchTagOperationMessage::class,
                    '',
                    []
                ),
            ],
            selectedElements: array_map(
                static fn (int $id) => new ElementDescriptor(
                    $parameters->getType(),
                    $id
                ),
                $childrenIds
            ),
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
}
