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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait\CsvExportHandlerSetupTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use function count;

/**
 * @internal
 */
#[AsMessageHandler]
final class ExportDataCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;
    use CsvExportHandlerSetupTrait;

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly ExportDataStorageServiceInterface $exportDataStorageService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly GridServiceInterface $gridService,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ExportDataCollectionMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $user = $this->userResolver->getById($jobRun->getOwnerId());

        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                [
                    'userId' => $jobRun->getOwnerId(),
                ]
            ));
        }

        $assets = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENTS_TO_EXPORT->value);
        $totalAssets = count($assets);
        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);

        $columnsDefinitions = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        $columnCollection = $this->gridService->getConfigurationForExport($columns, $columnsDefinitions);

        $assetsData = [];
        $jobStepName = $this->getJobStep($message)->getName();
        foreach ($assets as $asset) {
            try {
                $assetsData[$asset['id']] = $this->gridService->getGridValuesForElement(
                    $columnCollection,
                    ElementTypes::TYPE_ASSET,
                    $asset['id'],
                    $user
                );

            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                    [
                        'id' => $asset['id'],
                        'message' => $e->getMessage(),
                    ]
                ));
            }

            $this->updateProgress(
                $this->publishService,
                $this->userTopicService,
                $jobRun,
                $jobStepName,
                $totalAssets,
                $totalAssets,
                persist: false
            );
        }

        try {
            // The collected rows are not part of the job run: it is loaded and written again by every step, so
            // keeping the rows in its context makes each step more expensive than the one before.
            if (!empty($assetsData)) {
                $this->exportDataStorageService->store($jobRun->getId(), $message->getCurrentJobStep(), $assetsData);
            }
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'message' => $e->getMessage(),
                ]
            ));
        }
    }
}
