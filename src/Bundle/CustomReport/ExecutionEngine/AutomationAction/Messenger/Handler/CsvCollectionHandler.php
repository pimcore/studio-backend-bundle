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
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\AdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service\CustomReportConfigServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
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
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly CustomReportConfigServiceInterface $customReportConfigService,
        private readonly AdapterServiceInterface $customReportAdapterService,
        private readonly UserResolverInterface $userResolver
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

        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::USER_NOT_FOUND_MESSAGE->value,
                ['userId' => $jobRun->getOwnerId()]
            ));
        }

        $stepData = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CUSTOM_REPORT_CONFIG->value);
        $name = $stepData['name'];

        $reportConfig = null;

        try {
            $reportConfig = $this->customReportConfigService->getAllowedReportForUser($name, $user);
        } catch (NotFoundException $e) {
            $this->abort($this->getAbortData(
                Config::CSV_DATA_COLLECTION_FAILED_MESSAGE->value,
                [
                    'id' => $name,
                    'message' => $e->getMessage(),
                ]
            ));
        }

        if ($reportConfig === null) {
            $this->abort($this->getAbortData(
                Config::REPORT_PERMISSION_MISSING_MESSAGE->value,
                [
                    'userId' => $user->getId(),
                    'name' => $name,
                ]
            ));
        }

        try {
            $exportFields = $this->customReportConfigService->getFieldsForExport($reportConfig);
            $stepData['fields'] = $exportFields;
            $exportParameter = ExportParameter::fromArray($stepData);

            $reportData = $this->customReportAdapterService->getData(
                $reportConfig,
                $exportParameter
            );
            $csvData = $this->customReportConfigService->generateCsvData(
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

        $this->updateProgress(
            $this->publishService,
            $this->userTopicService,
            $jobRun,
            $this->getJobStep($message)->getName()
        );
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
