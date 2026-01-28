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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Class\Repository\ClassDefinitionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\Util\Trait\CsvExportHandlerSetupTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

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
        private readonly ClassDefinitionRepositoryInterface $classDefinitionRepository,
        private readonly DataObjectServiceInterface $dataObjectService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly UserResolverInterface $userResolver,
        private readonly GridServiceInterface $gridService
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

        $jobDataObject = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_TO_EXPORT->value);

        $dataObject = $this->dataObjectService->getDataObjectForUser($jobDataObject['id'], $user);
        $classId = $this->classDefinitionRepository->getClassDefinition($dataObject->getClassName())->getId();

        if ($dataObject->getType() === ElementTypes::TYPE_FOLDER) {
            $this->abort($this->getAbortData(
                Config::ELEMENT_FOLDER_COLLECTION_NOT_SUPPORTED->value,
                [
                    'folderId' => $dataObject->getId(),
                ]
            ));

            return;
        }

        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);
        $columnsDefinitions = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $classId,
            1,
            $user
        );

        $columnCollection = $this->gridService->getConfigurationForExport($columns, $columnsDefinitions);

        try {
            $dataObjectData = [
                $dataObject->getId() => $this->gridService->getGridValuesForElement(
                    $columnCollection,
                    ElementTypes::TYPE_OBJECT,
                    $dataObject->getId(),
                    true,
                    $user
                ),
            ];

            $this->updateContextArrayValues($jobRun, StepConfig::GRID_EXPORT_DATA->value, $dataObjectData);

            $csvExportDataInfo = $jobRun->getContext()[StepConfig::GRID_EXPORT_DATA_INFO->value] ?? null;

            if ($csvExportDataInfo === null) {
                $this->updateContextArrayValues(
                    $jobRun,
                    StepConfig::GRID_EXPORT_DATA_INFO->value,
                    [
                        'type' => ElementTypes::TYPE_OBJECT,
                        'classId' => $classId,
                    ]
                );
            }

        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'id' => $dataObject->getId(),
                    'message' => $e->getMessage(),
                ]
            ));
        }

        $this->updateProgress($this->publishService, $this->userTopicService, $jobRun, $this->getJobStep($message)->getName());
    }
}
