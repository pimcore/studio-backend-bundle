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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\ExecutionEngine\AutomationAction\Messenger\Messages\CsvCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\ExecutionEngine\Util\JobSteps as CustomReportJobSteps;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\MappedParameter\ExportParameter;
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
        private SecurityServiceInterface $securityService
    ) {
    }

    public function generateCsvFile(ExportParameter $exportParameter): int
    {
        $collectionSettings = [
            StepConfig::CUSTOM_REPORT_CONFIG->value => $exportParameter,
        ];

        return $this->generateCsvFileJob(
            $collectionSettings,
        );
    }

    private function generateCsvFileJob(
        array $collectionSettings
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
                []
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
