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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvAssetCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait\CsvExportHandlerSetupTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvAssetDataCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;
    use CsvExportHandlerSetupTrait;

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly GridServiceInterface $gridService,
        private readonly AssetServiceInterface $assetService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CsvAssetCollectionMessage $message): void
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

        $jobAsset = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_TO_EXPORT->value);

        $asset = $this->assetService->getAssetForUser($jobAsset['id'], $user);

        if ($asset->getType() === ElementTypes::TYPE_FOLDER) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value,
                [
                    'folderId' => $asset->getId(),
                ]
            ));

            return;
        }

        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);

        $columnsDefinitions = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        $columnCollection = $this->gridService->getConfigurationFromArray(
            $columns,
            $columnsDefinitions,
            true
        );

        try {
            $assetData = [
                $asset->getId() => $this->gridService->getGridValuesForElement(
                    $columnCollection,
                    $asset,
                    ElementTypes::TYPE_ASSET
                ),
            ];

            $this->updateContextArrayValues($jobRun, StepConfig::CSV_EXPORT_DATA->value, $assetData);

            $csvExportDataInfo = $jobRun->getContext()[StepConfig::CSV_EXPORT_DATA_INFO->value] ?? null;

            if ($csvExportDataInfo === null) {
                $this->updateContextArrayValues(
                    $jobRun,
                    StepConfig::CSV_EXPORT_DATA_INFO->value,
                    [
                        'type' => ElementTypes::TYPE_ASSET,
                    ]
                );
            }

        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'id' => $asset->getId(),
                    'message' => $e->getMessage(),
                ]
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }
}
