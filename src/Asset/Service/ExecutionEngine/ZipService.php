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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\ZipUploadMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\CreateZipParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorService;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use ZipArchive;

/**
 * @internal
 */
final readonly class ZipService implements ZipServiceInterface
{
    private const ZIP_ID_PLACEHOLDER = '{id}';

    public function __construct(
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private UploadServiceInterface $uploadService,
    ) {
    }

    public function getZipArchive(int $id, $create = true): ?ZipArchive
    {
        $zip = $this->getTempZipFilePath($id);

        $archive = new ZipArchive();

        $state = false;

        if (is_file($zip)) {
            $state = $archive->open($zip);
        }

        if (!$state && $create) {
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

    /**
     * @throws AccessDeniedException|EnvironmentException|ForbiddenException|NotFoundException
     */
    public function uploadZipAssets(
        UserInterface $user,
        UploadedFile $zipArchive,
        int $parentId,
        string $archiveId
    ): int {
        $this->uploadService->validateParent($user, $parentId);
        $job = new Job(
            name: Jobs::ZIP_FILE_UPLOAD->value,
            steps: [
                new JobStep(JobSteps::ZIP_UPLOADING->value, ZipUploadMessage::class, '', []),
            ],
            selectedElements: [new ElementDescriptor(
                $this->copyUploadZipFile($zipArchive, $parentId . ' - ' . $archiveId),
                $parentId
            )],
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_CONTINUE_ON_ERROR->value
        );

        return $jobRun->getId();
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
            TranslatorService::DOMAIN
        );

        return $this->getTempZipFilePath($jobRun->getId());
    }

    public function getTempZipFilePath(int|string $id, string $subject = self::DOWNLOAD_ZIP_FILE_PATH): string
    {
        return str_replace(self::ZIP_ID_PLACEHOLDER, (string)$id, $subject);
    }
    
    /**
     * @throws EnvironmentException
     */
    private function copyUploadZipFile(UploadedFile $archive, string $archiveId): string
    {
        $zip = $this->getTempZipFilePath($archiveId, self::UPLOAD_ZIP_FILE_PATH);

        if (is_file($zip)) {
            throw new EnvironmentException('Zip file already exists');
        }

        copy($archive->getRealPath(), $zip);

        return $zip;
    }
}
