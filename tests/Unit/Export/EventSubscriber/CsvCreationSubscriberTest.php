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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Export\EventSubscriber;

use Codeception\Test\Unit;
use Pimcore\Bundle\GenericExecutionEngineBundle\Event\JobRunStateChangedEvent;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\EventSubscriberServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Export\EventSubscriber\CsvCreationSubscriber;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;

/**
 * @internal
 */
final class CsvCreationSubscriberTest extends Unit
{
    /**
     * @dataProvider cleanedUpStatesProvider
     */
    public function testRemovesTheCollectedRowsIfTheJobRunDoesNotFinish(string $state): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->expects($this->once())->method('cleanUp')->with(5);

        $this->createSubscriber($exportDataStorageService)->onStateChanged(
            new JobRunStateChangedEvent(5, Jobs::CREATE_CSV->value, 1, JobRunStates::RUNNING->value, $state)
        );
    }

    /**
     * @dataProvider ignoredEventsProvider
     */
    public function testLeavesTheCollectedRowsAloneOtherwise(string $jobName, string $state): void
    {
        $exportDataStorageService = $this->createMock(ExportDataStorageServiceInterface::class);
        $exportDataStorageService->expects($this->never())->method('cleanUp');

        $this->createSubscriber($exportDataStorageService)->onStateChanged(
            new JobRunStateChangedEvent(5, $jobName, 1, JobRunStates::RUNNING->value, $state)
        );
    }

    public function cleanedUpStatesProvider(): array
    {
        return [
            'failed' => [JobRunStates::FAILED->value],
            'cancelled' => [JobRunStates::CANCELLED->value],
        ];
    }

    public function ignoredEventsProvider(): array
    {
        return [
            'other job failed' => [Jobs::CREATE_XLSX->value, JobRunStates::FAILED->value],
            'still running' => [Jobs::CREATE_CSV->value, JobRunStates::RUNNING->value],
        ];
    }

    private function createSubscriber(ExportDataStorageServiceInterface $exportDataStorageService): CsvCreationSubscriber
    {
        return new CsvCreationSubscriber(
            $this->createMock(EventSubscriberServiceInterface::class),
            $this->createMock(ExportServiceInterface::class),
            $exportDataStorageService,
            $this->createMock(UserTopicServiceInterface::class),
        );
    }
}
