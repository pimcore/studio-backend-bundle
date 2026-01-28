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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ExportFolderDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\FilterParameterMapperInterface;
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
final class ExportFolderDataCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
        private readonly FilterParameterMapperInterface $filterParameterMapper,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly GridServiceInterface $gridService,
        private readonly GridSearchInterface $gridSearch
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(ExportFolderDataCollectionMessage $message): void
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

        $jobFolder = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_TO_EXPORT->value);

        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);

        $filters = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_FILTERS->value);

        $assets = $this->gridSearch->searchElementIdsForUser(
            ElementTypes::TYPE_ASSET,
            new GridParameter(
                $jobFolder['id'],
                $columns,
                $this->filterParameterMapper->fromArray($filters)
            ),
            $user
        );

        if (count($assets) === 0) {
            $this->updateProgress($this->publishService, $this->userTopicService, $jobRun, $this->getJobStep($message)->getName());

            return;
        }

        $columnsDefinitions = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        $columnCollection = $this->gridService->getConfigurationForExport(
            $columns,
            $columnsDefinitions
        );

        foreach ($assets as $assetId) {
            try {
                $assetData = [
                    $assetId => $this->gridService->getGridValuesForElement(
                        $columnCollection,
                        ElementTypes::TYPE_ASSET,
                        $assetId,
                        true,
                        $user
                    ),
                ];

                $this->updateContextArrayValues($jobRun, StepConfig::GRID_EXPORT_DATA->value, $assetData);
            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                    [
                        'id' => $assetId,
                        'message' => $e->getMessage(),
                    ]
                ));
            }
        }

        $csvExportDataInfo = $jobRun->getContext()[StepConfig::GRID_EXPORT_DATA_INFO->value] ?? null;

        if ($csvExportDataInfo === null) {
            $this->updateContextArrayValues(
                $jobRun,
                StepConfig::GRID_EXPORT_DATA_INFO->value,
                [
                    'type' => ElementTypes::TYPE_ASSET,
                ]
            );
        }

        $this->updateProgress($this->publishService, $this->userTopicService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::ELEMENT_TO_EXPORT->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::ELEMENT_TO_EXPORT->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_COLUMNS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_COLUMNS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_FILTERS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_FILTERS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }
}
