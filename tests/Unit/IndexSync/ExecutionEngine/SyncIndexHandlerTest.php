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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\IndexSync\ExecutionEngine;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\IndexSyncAlreadyRunningException;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexSyncServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use Pimcore\Bundle\GenericExecutionEngineBundle\Extractor\JobRunExtractorInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobRunStates;
use Pimcore\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Handler\SyncIndexHandler;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Messages\SyncIndexMessage;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Model\User;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
final class SyncIndexHandlerTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testSyncsTheIndexForAUserWithSystemSettingsPermission(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->expects($this->once())->method('syncAll');

        $this->createHandler($indexSyncService, $this->createUser(true))(new SyncIndexMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    public function testAbortsWithoutSyncingWhenTheUserLacksTheSystemSettingsPermission(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->expects($this->never())->method('syncAll');

        $this->expectExceptionMessage(Config::INDEX_SYNC_PERMISSION_MISSING->value);

        $this->createHandler($indexSyncService, $this->createUser(false))(new SyncIndexMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    public function testAbortsWhenTheOwnerOfTheJobRunNoLongerExists(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->expects($this->never())->method('syncAll');

        $this->expectExceptionMessage(Config::INDEX_SYNC_PERMISSION_MISSING->value);

        $this->createHandler($indexSyncService, null)(new SyncIndexMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    public function testReportsAnAlreadyRunningSynchronization(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->method('syncAll')->willThrowException(new IndexSyncAlreadyRunningException());

        $this->expectExceptionMessage(Config::INDEX_SYNC_ALREADY_RUNNING->value);

        $this->createHandler($indexSyncService, $this->createUser(true))(new SyncIndexMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    public function testReportsAFailedSynchronization(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->method('syncAll')->willThrowException(new Exception('index unreachable'));

        $this->expectExceptionMessage(Config::INDEX_SYNC_FAILED->value);

        $this->createHandler($indexSyncService, $this->createUser(true))(new SyncIndexMessage(1, 0));
    }

    /**
     * @throws Exception
     */
    public function testDoesNothingWhenTheJobRunIsNotRunning(): void
    {
        $indexSyncService = $this->createMock(IndexSyncServiceInterface::class);
        $indexSyncService->expects($this->never())->method('syncAll');

        $this->createHandler($indexSyncService, $this->createUser(true), JobRunStates::FAILED)(
            new SyncIndexMessage(1, 0)
        );
    }

    private function createUser(bool $allowed): User
    {
        $user = new User();
        $user->setPermissions($allowed ? [UserPermissions::SYSTEM_SETTINGS->value] : []);

        return $user;
    }

    private function createHandler(
        IndexSyncServiceInterface $indexSyncService,
        ?User $user,
        JobRunStates $state = JobRunStates::RUNNING,
    ): SyncIndexHandler {
        $userResolver = $this->createMock(UserResolverInterface::class);
        $userResolver->method('getById')->willReturn($user);

        $jobRun = new JobRun(1);
        $jobRun->setState($state);
        $jobRun->setCurrentStep(0);
        (new ReflectionProperty(JobRun::class, 'id'))->setValue($jobRun, 1);

        $jobRunExtractor = $this->createMock(JobRunExtractorInterface::class);
        $jobRunExtractor->method('getJobRun')->willReturn($jobRun);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        $handler = new SyncIndexHandler($indexSyncService, $userResolver);
        $handler->setJobRunRepository($this->createMock(JobRunRepositoryInterface::class));
        $handler->setJobRunExtractor($jobRunExtractor);
        $handler->setTranslator($translator);

        return $handler;
    }
}
