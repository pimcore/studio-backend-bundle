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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\BatchDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RecycleBinMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\ChunkGeneratorTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class DeleteService implements DeleteServiceInterface
{
    use ChunkGeneratorTrait;

    private const int DELETE_BATCH_SIZE = 500;

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent
    ) {
    }

    public function deleteElementsWithExecutionEngine(
        ElementInterface $element,
        UserInterface $user,
        string $elementType,
        array $childrenIds,
        bool $useRecycleBin
    ): int {
        $jobSteps = [];
        if ($useRecycleBin) {
            $jobSteps[] = new JobStep(
                JobSteps::ELEMENT_RECYCLING->value,
                RecycleBinMessage::class,
                '',
                []
            );
        }

        // Append parent ID to the end so it's deleted last
        $allIds = array_merge($childrenIds, [$element->getId()]);

        foreach ($this->chunkGenerator($allIds, self::DELETE_BATCH_SIZE) as $batch) {
            $jobSteps[] = new JobStep(
                JobSteps::ELEMENT_DELETION->value,
                ElementDeleteMessage::class,
                '',
                [StepConfig::ITEMS_TO_DELETE->value => $batch],
            );
        }

        $job = new Job(
            name: $this->getJobName($elementType),
            steps: $jobSteps,
            selectedElements: [
                new ElementDescriptor(
                    $elementType,
                    $element->getId()
                ),
            ]
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    public function batchDeleteElements(
        array $elements,
        UserInterface $user,
        string $elementType,
    ): int {
        $elementIds = array_map(
            static fn (ElementInterface $element) => $element->getId(),
            $elements
        );

        $jobSteps = [];
        foreach ($this->chunkGenerator($elementIds, self::DELETE_BATCH_SIZE) as $batch) {
            $jobSteps[] = new JobStep(
                JobSteps::ELEMENT_DELETION->value,
                BatchDeleteMessage::class,
                '',
                [
                    StepConfig::ITEMS_TO_BATCH_DELETE->value => $batch,
                    StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value => $elementType,
                ],
            );
        }

        $job = new Job(
            name: $this->getBatchJobName($elementType),
            steps: $jobSteps
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getJobName(string $type): string
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => Jobs::DELETE_ASSETS->value,
            ElementTypes::TYPE_DOCUMENT => Jobs::DELETE_DOCUMENTS->value,
            ElementTypes::TYPE_OBJECT => Jobs::DELETE_DATA_OBJECTS->value,
            default => throw new InvalidElementTypeException($type),
        };
    }

    /**
     * @throws InvalidElementTypeException
     */
    private function getBatchJobName(string $type): string
    {
        return match ($type) {
            ElementTypes::TYPE_ASSET => Jobs::BATCH_DELETE_ASSETS->value,
            ElementTypes::TYPE_DATA_OBJECT => Jobs::BATCH_DELETE_DATA_OBJECTS->value,
            default => throw new InvalidElementTypeException($type),
        };
    }
}
