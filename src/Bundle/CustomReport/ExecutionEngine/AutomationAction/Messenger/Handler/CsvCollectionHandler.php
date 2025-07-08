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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvCollectionHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly CustomReportServiceInterface $customReportService,
        private readonly AdapterServiceInterface $customReportAdapterService
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CsvCollectionMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }
        $name = '';

        try {
            $exportParameter = ExportParameter::fromArray(
                $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CUSTOM_REPORT_CONFIG->value)
            );
            $name = $exportParameter->getName();
            $reportConfig = $this->customReportService->getCustomReportByName($name);
            $exportFields = $this->customReportService->getFieldsForExport($reportConfig);
            $reportData = $this->customReportAdapterService->getData(
                $reportConfig,
                $exportParameter
            );
            $csvData = $this->customReportService->generateCsvData(
                $reportData,
                $exportFields,
                $exportParameter->getIncludeHeaders()
            );

            $this->updateContextArrayValues(
                $this->getJobRun($message),
                StepConfig::GRID_EXPORT_DATA->value,
                $csvData
            );
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'id' => $name,
                    'message' => $e->getMessage(),
                ]
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(StepConfig::CUSTOM_REPORT_CONFIG->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CUSTOM_REPORT_CONFIG->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
    }
}
