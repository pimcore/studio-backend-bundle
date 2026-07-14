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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\ExecutionEngine\Util\JobSteps as CustomReportJobSteps;
use Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\MappedParameter\ExportParameter;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\StepConfig;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Export\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

/**
 * @internal
 */
final readonly class CsvService implements CsvServiceInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private CustomReportConfigServiceInterface $customReportConfigService
    ) {
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function generateCsvFile(ExportParameter $exportParameter): int
    {
        $this->customReportConfigService->getAllowedReport($exportParameter->getName());

        $collectionSettings = [
            StepConfig::CUSTOM_REPORT_CONFIG->value => $exportParameter,
        ];

        $creationSettings = [
            StepConfig::CONFIG_CONFIGURATION->value => [
                StepConfig::SETTINGS_DELIMITER->value => $exportParameter->getDelimiter(),
            ],
        ];

        return $this->generateCsvFileJob($collectionSettings, $creationSettings);
    }

    private function generateCsvFileJob(
        array $collectionSettings,
        array $creationSettings
    ): int {

        $jobSteps = [
            new JobStep(
                CustomReportJobSteps::CUSTOM_REPORT_CSV_COLLECTION->value,
                CsvCollectionMessage::class,
                '',
                $collectionSettings
            ),
            new JobStep(
                JobSteps::CSV_CREATION->value,
                CsvCreationMessage::class,
                '',
                $creationSettings
            ),
        ];

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            new Job(Jobs::CREATE_CSV->value, $jobSteps),
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
