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

namespace Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\AutomationAction\Messenger\Messages\BulkImportCleanupMessage;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportFileServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\User\Resolver\UserAccountResolverInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class BulkImportCleanupHandler extends AbstractHandler
{
    public function __construct(
        private readonly BulkImportFileServiceInterface $bulkImportFileService,
        private readonly UserAccountResolverInterface $userAccountResolver,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(BulkImportCleanupMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userAccountResolver,
            [EnvironmentVariables::BULK_IMPORT_FILE_ID->value]
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $environmentData = $validatedParameters->getEnvironmentData();
        $fileId = $environmentData[EnvironmentVariables::BULK_IMPORT_FILE_ID->value];

        $this->bulkImportFileService->cleanUpFile($fileId);
    }
}
