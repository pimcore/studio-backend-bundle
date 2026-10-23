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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\BatchDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\ElementDeleteMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\AutomationAction\Messenger\Messages\RecycleBinMessage;
use Pimcore\Bundle\StudioBackendBundle\Element\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ExecutionEngine\DeleteService;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class DeleteServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testChunksChildrenIntoMultipleSteps(): void
    {
        $childrenIds = range(100, 1599); // 1500 children
        $parentId = 1;
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 42]);
            }
        );

        $result = $service->deleteElementsWithExecutionEngine(
            $this->makeElement($parentId),
            $this->makeUser(5),
            ElementTypes::TYPE_ASSET,
            $childrenIds,
            false,
        );

        $this->assertSame(42, $result);

        // 1500 children + 1 parent = 1501 items → 4 steps (500 + 500 + 500 + 1)
        $steps = $capturedJob->getSteps();
        $this->assertCount(4, $steps);

        // First 3 steps have 500 items each
        for ($i = 0; $i < 3; $i++) {
            $batch = $steps[$i]->getConfig()[StepConfig::ITEMS_TO_DELETE->value];
            $this->assertCount(500, $batch);
        }

        // Last step has 1 item (the parent)
        $lastBatch = $steps[3]->getConfig()[StepConfig::ITEMS_TO_DELETE->value];
        $this->assertCount(1, $lastBatch);
        $this->assertSame($parentId, $lastBatch[0]);
    }

    /**
     * @throws Exception
     */
    public function testParentIdIsAppendedAfterChildren(): void
    {
        $childrenIds = [10, 20, 30];
        $parentId = 5;
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->deleteElementsWithExecutionEngine(
            $this->makeElement($parentId),
            $this->makeUser(1),
            ElementTypes::TYPE_OBJECT,
            $childrenIds,
            false,
        );

        $batch = $capturedJob->getSteps()[0]->getConfig()[StepConfig::ITEMS_TO_DELETE->value];
        $this->assertSame([10, 20, 30, 5], $batch);
    }

    /**
     * @throws Exception
     */
    public function testRecycleBinStepPrependedWhenEnabled(): void
    {
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->deleteElementsWithExecutionEngine(
            $this->makeElement(1),
            $this->makeUser(1),
            ElementTypes::TYPE_ASSET,
            [2, 3],
            true,
        );

        $steps = $capturedJob->getSteps();
        // First step is recycling, then deletion
        $this->assertSame(RecycleBinMessage::class, $steps[0]->getMessageFQCN());
        $this->assertSame(JobSteps::ELEMENT_RECYCLING->value, $steps[0]->getName());
        $this->assertSame(ElementDeleteMessage::class, $steps[1]->getMessageFQCN());
    }

    /**
     * @throws Exception
     */
    public function testNoRecycleBinStepWhenDisabled(): void
    {
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->deleteElementsWithExecutionEngine(
            $this->makeElement(1),
            $this->makeUser(1),
            ElementTypes::TYPE_ASSET,
            [],
            false,
        );

        $steps = $capturedJob->getSteps();
        $this->assertCount(1, $steps);
        $this->assertSame(ElementDeleteMessage::class, $steps[0]->getMessageFQCN());
    }

    /**
     * @throws Exception
     */
    public function testUsesContinueOnErrorContext(): void
    {
        $capturedContext = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job, ?int $ownerId, string $context) use (&$capturedContext) {
                $capturedContext = $context;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->deleteElementsWithExecutionEngine(
            $this->makeElement(1),
            $this->makeUser(1),
            ElementTypes::TYPE_ASSET,
            [],
            false,
        );

        $this->assertSame(Config::CONTEXT_CONTINUE_ON_ERROR->value, $capturedContext);
    }

    /**
     * @throws Exception
     */
    public function testJobNameMatchesElementType(): void
    {
        $capturedJobs = [];

        $typeToJob = [
            ElementTypes::TYPE_ASSET => Jobs::DELETE_ASSETS->value,
            ElementTypes::TYPE_DOCUMENT => Jobs::DELETE_DOCUMENTS->value,
            ElementTypes::TYPE_OBJECT => Jobs::DELETE_DATA_OBJECTS->value,
        ];

        foreach ($typeToJob as $type => $expectedName) {
            $service = $this->createDeleteService(
                startJobExecution: function (Job $job) use (&$capturedJobs, $type) {
                    $capturedJobs[$type] = $job;

                    return $this->makeEmpty(JobRun::class, ['getId' => 1]);
                }
            );

            $service->deleteElementsWithExecutionEngine(
                $this->makeElement(1),
                $this->makeUser(1),
                $type,
                [],
                false,
            );
        }

        foreach ($typeToJob as $type => $expectedName) {
            $this->assertSame($expectedName, $capturedJobs[$type]->getName());
        }
    }

    /**
     * @throws Exception
     */
    public function testSelectedElementsContainsParentDescriptor(): void
    {
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->deleteElementsWithExecutionEngine(
            $this->makeElement(99),
            $this->makeUser(1),
            ElementTypes::TYPE_DOCUMENT,
            [200, 300],
            false,
        );

        $selectedElements = $capturedJob->getSelectedElements();
        $this->assertCount(1, $selectedElements);
        $this->assertSame(99, $selectedElements[0]->getId());
        $this->assertSame(ElementTypes::TYPE_DOCUMENT, $selectedElements[0]->getType());
    }

    /**
     * @throws Exception
     */
    public function testBatchDeleteChunksElementsIntoMultipleSteps(): void
    {
        $elements = [];
        for ($i = 1; $i <= 1200; $i++) {
            $elements[] = $this->makeElement($i);
        }
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 55]);
            }
        );

        $result = $service->batchDeleteElements($elements, $this->makeUser(1), ElementTypes::TYPE_ASSET);

        $this->assertSame(55, $result);

        $steps = $capturedJob->getSteps();
        $this->assertCount(3, $steps);

        // First batch: IDs 1–500
        $batch1 = $steps[0]->getConfig()[StepConfig::ITEMS_TO_BATCH_DELETE->value];
        $this->assertCount(500, $batch1);
        $this->assertSame(1, $batch1[0]);
        $this->assertSame(500, $batch1[499]);

        // Second batch: IDs 501–1000
        $batch2 = $steps[1]->getConfig()[StepConfig::ITEMS_TO_BATCH_DELETE->value];
        $this->assertCount(500, $batch2);
        $this->assertSame(501, $batch2[0]);

        // Third batch: IDs 1001–1200
        $batch3 = $steps[2]->getConfig()[StepConfig::ITEMS_TO_BATCH_DELETE->value];
        $this->assertCount(200, $batch3);
        $this->assertSame(1001, $batch3[0]);
        $this->assertSame(1200, $batch3[199]);

        foreach ($steps as $step) {
            $this->assertSame(BatchDeleteMessage::class, $step->getMessageFQCN());
            $this->assertSame(
                ElementTypes::TYPE_ASSET,
                $step->getConfig()[StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value]
            );
        }
    }

    /**
     * @throws Exception
     */
    public function testBatchDeleteSingleBatch(): void
    {
        $elements = [$this->makeElement(10), $this->makeElement(20), $this->makeElement(30)];
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->batchDeleteElements($elements, $this->makeUser(1), ElementTypes::TYPE_DATA_OBJECT);

        $steps = $capturedJob->getSteps();
        $this->assertCount(1, $steps);

        $batch = $steps[0]->getConfig()[StepConfig::ITEMS_TO_BATCH_DELETE->value];
        $this->assertSame([10, 20, 30], $batch);
        $this->assertSame(
            ElementTypes::TYPE_DATA_OBJECT,
            $steps[0]->getConfig()[StepConfig::ELEMENT_TYPE_TO_BATCH_DELETE->value]
        );
    }

    /**
     * @throws Exception
     */
    public function testBatchDeleteJobNameMatchesElementType(): void
    {
        $capturedJobs = [];

        $typeToJob = [
            ElementTypes::TYPE_ASSET => Jobs::BATCH_DELETE_ASSETS->value,
            ElementTypes::TYPE_DATA_OBJECT => Jobs::BATCH_DELETE_DATA_OBJECTS->value,
        ];

        foreach ($typeToJob as $type => $expectedName) {
            $service = $this->createDeleteService(
                startJobExecution: function (Job $job) use (&$capturedJobs, $type) {
                    $capturedJobs[$type] = $job;

                    return $this->makeEmpty(JobRun::class, ['getId' => 1]);
                }
            );

            $service->batchDeleteElements(
                [$this->makeElement(1)],
                $this->makeUser(1),
                $type,
            );
        }

        foreach ($typeToJob as $type => $expectedName) {
            $this->assertSame($expectedName, $capturedJobs[$type]->getName());
        }
    }

    /**
     * @throws Exception
     */
    public function testBatchDeleteHasNoSelectedElements(): void
    {
        $capturedJob = null;

        $service = $this->createDeleteService(
            startJobExecution: function (Job $job) use (&$capturedJob) {
                $capturedJob = $job;

                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            }
        );

        $service->batchDeleteElements(
            [$this->makeElement(1)],
            $this->makeUser(1),
            ElementTypes::TYPE_ASSET,
        );

        $this->assertEmpty($capturedJob->getSelectedElements());
    }

    /**
     * @throws Exception
     */
    private function createDeleteService(?callable $startJobExecution = null): DeleteService
    {
        $jobExecutionAgent = $this->makeEmpty(JobExecutionAgentInterface::class, [
            'startJobExecution' => $startJobExecution ?? function () {
                return $this->makeEmpty(JobRun::class, ['getId' => 1]);
            },
        ]);

        return new DeleteService($jobExecutionAgent);
    }

    /**
     * @throws Exception
     */
    private function makeElement(int $id): ElementInterface
    {
        return $this->makeEmpty(ElementInterface::class, ['getId' => $id]);
    }

    /**
     * @throws Exception
     */
    private function makeUser(int $id): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => $id]);
    }
}
