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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\AutomationAction\Messenger\Messages\BulkImportMessage;
use Pimcore\Bundle\StudioBackendBundle\Class\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportDataResolver;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportFileServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\BulkImport\BulkImportExecutorInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Util\ClassDefinitionType;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Model\AbortActionData;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class BulkImportHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly BulkImportExecutorInterface $bulkImportExecutor,
        private readonly BulkImportFileServiceInterface $bulkImportFileService,
        private readonly BulkImportDataResolver $bulkImportDataResolver,
        private readonly UserResolverInterface $userResolver,
        private readonly PublishServiceInterface $publishService,
        private readonly UserTopicServiceInterface $userTopicService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(BulkImportMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $validatedParameters = $this->validateJobParameters(
            $message,
            $jobRun,
            $this->userResolver,
            [
                EnvironmentVariables::BULK_IMPORT_FILE_ID->value,
                EnvironmentVariables::BULK_IMPORT_ITEMS->value,
            ]
        );

        if ($validatedParameters instanceof AbortActionData) {
            $this->abort($validatedParameters);
        }

        $user = $validatedParameters->getUser();
        $environmentData = $validatedParameters->getEnvironmentData();

        $fileId = $environmentData[EnvironmentVariables::BULK_IMPORT_FILE_ID->value];
        $requestedItems = $environmentData[EnvironmentVariables::BULK_IMPORT_ITEMS->value];

        $importTypeValue = $this->extractConfigFieldFromJobStepConfig(
            $message,
            StepConfig::IMPORT_TYPE->value
        );

        $importType = ClassDefinitionType::tryFrom($importTypeValue);
        if ($importType === null) {
            $this->abort($this->getAbortData(
                Config::BULK_IMPORT_FAILED_MESSAGE->value,
                ['type' => $importTypeValue, 'name' => '', 'message' => 'Unknown import type'],
            ));
        }

        try {
            $fileData = $this->bulkImportFileService->readFileData($fileId);
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::BULK_IMPORT_FAILED_MESSAGE->value,
                [
                    'type' => $importType->value,
                    'name' => '',
                    'message' => $e->getMessage(),
                ],
            ));
        }

        $requestedIndex = $this->bulkImportDataResolver->buildRequestedItemsIndex($requestedItems);
        $dataForType = $fileData[$importType->value] ?? [];
        $filtered = $this->bulkImportDataResolver->filterItemsForType(
            $dataForType,
            $importType,
            $requestedIndex
        );

        $jobName = $this->getJobStep($message)->getName();
        $totalItems = $filtered['count'];

        foreach ($filtered['items'] as $filteredItem) {
            try {
                $this->bulkImportExecutor->importSingleItem(
                    $importType,
                    $filteredItem['name'],
                    $filteredItem['entry'],
                    $user,
                );
            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::BULK_IMPORT_FAILED_MESSAGE->value,
                    [
                        'type' => $importType->value,
                        'name' => $filteredItem['name'],
                        'message' => $e->getMessage(),
                    ],
                ));
            }

            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $jobName,
                $totalItems
            );
        }
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::IMPORT_TYPE->value);
        $this->stepConfiguration->setAllowedTypes(StepConfig::IMPORT_TYPE->value, 'string');
    }
}
