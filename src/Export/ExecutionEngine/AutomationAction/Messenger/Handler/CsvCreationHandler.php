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

namespace Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Handler;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\Model\GridExportData;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportDataStorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Export\Service\ExportServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UserTopicServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvCreationHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    public function __construct(
        private readonly ExportDataStorageServiceInterface $exportDataStorageService,
        private readonly PublishServiceInterface $publishService,
        private readonly UserResolverInterface $userResolver,
        private readonly UserTopicServiceInterface $userTopicService,
        private readonly ExportServiceInterface $csvExportService,

    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function __invoke(CsvCreationMessage $message): void
    {
        $jobRun = $this->getJobRun($message);
        if (!$this->shouldBeExecuted($jobRun)) {
            return;
        }

        $user = $this->userResolver->getById($jobRun->getOwnerId());
        if ($user === null) {
            $this->abort($this->getAbortData(
                Config::XLSX_CREATION_FAILED_MESSAGE->value,
                ['message' => 'User not found']
            ));
        }

        $columns = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_COLUMNS->value);
        $settings = $this->extractConfigFieldFromJobStepConfig($message, StepConfig::CONFIG_CONFIGURATION->value);
        $elementType = $this->extractOptionalConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_TYPE->value);
        $classId = $this->extractOptionalConfigFieldFromJobStepConfig($message, StepConfig::ELEMENT_CLASS_ID->value);
        $headers = $settings[StepConfig::SETTINGS_HEADER->value] ?? StepConfig::SETTINGS_HEADER_NO_HEADER->value;
        $delimiter = $settings[StepConfig::SETTINGS_DELIMITER->value] ?? null;

        $csvData = null;

        try {
            $csvData = $this->exportDataStorageService->load($jobRun->getId());
        } catch (EnvironmentException $e) {
            $this->abort($this->getAbortData(
                Config::CSV_CREATION_FAILED_MESSAGE->value,
                ['message' => $e->getMessage()]
            ));
        }

        // A job run that was already collecting when the rows moved out of the job run still carries the rows of its
        // first batches in the context, they come before the stored ones
        $contextData = $jobRun->getContext()[StepConfig::GRID_EXPORT_DATA->value] ?? null;
        if ($contextData !== null) {
            $csvData = array_merge($contextData, $csvData ?? []);
        }
        if ($csvData === null) {
            $this->abort($this->getAbortData(
                Config::CSV_CREATION_FAILED_MESSAGE->value,
                ['message' => 'Csv export data not found']
            ));
        }

        try {
            $this->csvExportService->createExportFile(
                $jobRun->getId(),
                new GridExportData(
                    $columns,
                    $csvData,
                    ['type' => $elementType, 'classId' => $classId],
                    $headers !== StepConfig::SETTINGS_HEADER_NO_HEADER->value,
                    $headers === StepConfig::SETTINGS_HEADER_NAME,
                ),
                $user,
                $delimiter,
            );
        } catch (Exception $e) {
            $this->abort($this->getAbortData(
                Config::CSV_CREATION_FAILED_MESSAGE->value,
                ['message' => $e->getMessage()]
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
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_CONFIGURATION->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_CONFIGURATION->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setRequired(StepConfig::CONFIG_COLUMNS->value);
        $this->stepConfiguration->setAllowedTypes(
            StepConfig::CONFIG_COLUMNS->value,
            StepConfig::CONFIG_TYPE_ARRAY->value
        );
        $this->stepConfiguration->setDefaults([
            StepConfig::CONFIG_COLUMNS->value => [],
            StepConfig::CONFIG_CONFIGURATION->value => [],
            StepConfig::ELEMENT_TYPE->value => null,
            StepConfig::ELEMENT_CLASS_ID->value => null,
        ]);
    }
}
