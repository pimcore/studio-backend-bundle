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
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine\CsvServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Util\Constants\Csv;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\AbstractHandler;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Trait\HandlerProgressTrait;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\PublishServiceInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * @internal
 */
#[AsMessageHandler]
final class CsvCreationHandler extends AbstractHandler
{
    use HandlerProgressTrait;

    private const ARRAY_TYPE = 'array';

    public function __construct(
        private readonly PublishServiceInterface $publishService,
        private readonly CsvServiceInterface $csvService,
        private readonly GridServiceInterface $gridService
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
        $settings = $this->extractConfigFieldFromJobStepConfig($message, Csv::JOB_STEP_CONFIG_SETTINGS->value);
        $columnCollection = $this->gridService->getConfigurationFromArray(
            $this->extractConfigFieldFromJobStepConfig($message, Csv::JOB_STEP_CONFIG_CONFIGURATION->value)
        );
        if (!isset($jobRun->getContext()[Csv::ASSET_EXPORT_DATA->value])) {
            $this->abort($this->getAbortData(
                Config::CSV_CREATION_FAILED_MESSAGE->value,
                ['message' => 'Asset export data not found in job run context']
            ));
        }
        $assetData = $jobRun->getContext()[Csv::ASSET_EXPORT_DATA->value];

        try {
            $this->csvService->createCsvFile(
                $jobRun->getId(),
                $columnCollection,
                $settings,
                $assetData,
            );
        } catch (Exception|FilesystemException $e) {
            $this->abort($this->getAbortData(
                Config::CSV_CREATION_FAILED_MESSAGE->value,
                ['message' => $e->getMessage()]
            ));
        }

        $this->updateProgress($this->publishService, $jobRun, $this->getJobStep($message)->getName());
    }

    protected function configureStep(): void
    {
        $this->stepConfiguration->setRequired(Csv::JOB_STEP_CONFIG_SETTINGS->value);
        $this->stepConfiguration->setAllowedTypes(
            Csv::JOB_STEP_CONFIG_SETTINGS->value,
            self::ARRAY_TYPE
        );
        $this->stepConfiguration->setRequired(Csv::JOB_STEP_CONFIG_CONFIGURATION->value);
        $this->stepConfiguration->setAllowedTypes(
            Csv::JOB_STEP_CONFIG_CONFIGURATION->value,
            self::ARRAY_TYPE
        );
    }
}
