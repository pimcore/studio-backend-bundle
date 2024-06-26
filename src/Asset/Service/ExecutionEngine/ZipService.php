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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCollectionMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipCreationMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateZipParameter;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\Asset;
use ZipArchive;

/**
 * @internal
 */
final readonly class ZipService implements ZipServiceInterface
{
    private const ZIP_FILE_PATH = PIMCORE_SYSTEM_TEMP_DIRECTORY . '/download-zip-{id}.zip';

    private const ZIP_ID_PLACEHOLDER = '{id}';

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function getZipArchive(int $id): ?ZipArchive
    {
        $zip = $this->getTempZipFilePath($id);

        $archive = new ZipArchive();

        $state = false;

        if (is_file($zip)) {
            $state = $archive->open($zip);
        }

        if (!$state) {
            $state = $archive->open($zip, ZipArchive::CREATE);
        }

        if (!$state) {
            return null;
        }

        return $archive;
    }

    public function addFile(ZipArchive $archive, Asset $asset): void
    {
        $archive->addFile(
            $asset->getLocalFile(),
            preg_replace(
                '@^' . preg_quote($asset->getRealPath(), '@') . '@i',
                '',
                $asset->getRealFullPath()
            )
        );
    }

    public function generateZipFile(CreateZipParameter $ids): string
    {
        $steps = [
            new JobStep(JobSteps::ZIP_COLLECTION->value, ZipCollectionMessage::class, '', []),
            new JobStep(JobSteps::ZIP_CREATION->value, ZipCreationMessage::class, '', []),
        ];

        $job = new Job(
            name: Jobs::CREATE_ZIP->value,
            steps: $steps,
            selectedElements: $ids->getItems()
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $this->securityService->getCurrentUser()->getId(),
            Config::CONTEXT->value
        );

        return $this->getTempZipFilePath($jobRun->getId());
    }

    public function getTempZipFilePath(int $id): string
    {
        return str_replace(self::ZIP_ID_PLACEHOLDER, (string)$id, self::ZIP_FILE_PATH);
    }
}
