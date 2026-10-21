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

namespace Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Handler;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Exception\IndexSyncAlreadyRunningException;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexSyncServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\ExecutionEngine\Messages\SyncIndexMessage;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class SyncIndexHandler extends AbstractHandler
{
    public function __construct(
        private readonly IndexSyncServiceInterface $indexSyncService,
        private readonly UserResolverInterface $userResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(SyncIndexMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $ownerId = $jobRun->getOwnerId();
        $user = $ownerId === null ? null : $this->userResolver->getById($ownerId);
        if ($user === null || !$user->isAllowed(UserPermissions::SYSTEM_SETTINGS->value)) {
            $this->abort($this->getAbortData(
                Config::INDEX_SYNC_PERMISSION_MISSING->value,
                ['userId' => $ownerId]
            ));
        }

        try {
            $this->indexSyncService->syncAll();
        } catch (IndexSyncAlreadyRunningException) {
            $this->abort($this->getAbortData(Config::INDEX_SYNC_ALREADY_RUNNING->value));
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::INDEX_SYNC_FAILED->value,
                ['message' => $e->getMessage()]
            ));
        }
    }

    protected function configureStep(): void
    {
    }
}
