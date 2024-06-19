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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\DownloadIdsParameter;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ZipServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;

final readonly class ZipDownloadService implements ZipDownloadServiceInterface
{
    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private ZipServiceInterface $zipService
    ) {
    }

    public function generateZipFile(DownloadIdsParameter $ids): string
    {
        $job = new Job(
            name: Jobs::CREATE_ZIP->value,
            steps: [
                new JobStep('Asset collection', ZipCollectionMessage::class, '', []),
                new JobStep('Zip creation', ZipCreationMessage::class, '', []),
            ],
            selectedElements: $ids->getItems()
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            'studio_backend'
        );

        return $this->zipService->getTempZipFilePath($jobRun->getId());
    }
}
