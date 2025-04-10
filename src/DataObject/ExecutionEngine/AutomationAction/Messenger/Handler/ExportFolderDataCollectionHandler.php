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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\ExportFolderDataCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Mapper\FilterParameterMapperInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
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

        $dataObjects = $this->gridSearch->searchElementsForUser(
            'object',
            new GridParameter(
                $jobFolder['id'],
                $columns,
                $this->filterParameterMapper->fromArray($filters)
            ),
            $user
        );

        if (count($dataObjects->getItems()) === 0) {
            $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());

            return;
        }

        $dataObject = $dataObjects->getItems()[0];

        $className = $dataObject->getClassName();
        $columnsDefinitions = $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
            $className,
            $jobFolder['id'],
            $user
        );

        $columnCollection = $this->gridService->getConfigurationForExport(
            $columns,
            $columnsDefinitions
        );

        foreach ($dataObjects->getItems() as $dataObject) {
            try {
                $dataObjectData = [
                    $dataObject->getId() => $this->gridService->getGridValuesForElement(
                        $columnCollection,
                        $dataObject,
                        ElementTypes::TYPE_OBJECT,
                        true
                    ),
                ];

                $this->updateContextArrayValues($jobRun, StepConfig::GRID_EXPORT_DATA->value, $dataObjectData);
            } catch (Exception $e) {
                $this->abort($this->getAbortData(
                    Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                    [
                        'id' => $dataObject->getId(),
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
                    'type' => ElementTypes::TYPE_OBJECT,
                    'className' => $className,
                ]
            );
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
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
