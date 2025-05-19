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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine;

use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Service\DocumentSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Data\Model\CloneData;
use Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\AutomationAction\Messenger\Messages\CloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Document\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentCloneParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Service as DocumentService;
use Pimcore\Model\Element\ValidationException;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class CloneService implements CloneServiceInterface
{
    private DocumentService $coreDocumentService;

    public function __construct(
        private DocumentServiceInterface $documentService,
        private DocumentSearchServiceInterface $documentSearchService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
        $this->coreDocumentService = new DocumentService();
    }

    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneDocuments(int $sourceId, int $parentId, DocumentCloneParameters $parameters): ?int
    {
        $user = $this->securityService->getCurrentUser();
        $source = $this->documentService->getDocumentElement($user, $sourceId,);
        $cloneData = new CloneData(
            $parameters->getLanguage(),
            $parameters->isEnableInheritance(),
            $parameters->isUpdateReferences()
        );
        $newParent = $this->cloneDocument(
            $source,
            $this->documentService->getDocumentElement($user, $parentId),
            $this->securityService->getCurrentUser(),
            $cloneData
        );

        if (!$parameters->isRecursive() || !$source->hasChildren()) {
            return null;
        }

        return $this->cloneChildrenWithExecutionEngine(
            user: $user,
            originalParent: $source,
            newParent: $newParent,
            cloneData: $cloneData,
        );

    }

    /**
     * {@inheritdoc}
     */
    public function cloneDocument(
        Document $source,
        Document $parent,
        UserInterface $user,
        CloneData $cloneData
    ): Document {
        if (!$parent->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing permissions on target element %s',
                    $parent->getId()
                )
            );
        }

        $this->synchronousProcessingService->enable();

        try {
            $document = $this->coreDocumentService->copyAsChild(
                $parent,
                $source,
                $cloneData->isEnableInheritance(),
                $cloneData->isUpdateReferences(),
                $cloneData->getLanguage(),
            );
        } catch (ValidationException $exception) {
            throw new ValidationFailedException(
                message: sprintf('Failed to clone document: %s', $exception->getMessage()),
                previous: $exception
            );
        }

        return $document;
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        Document $source,
        int $originalParentId,
        int $parentId,
    ): Document {
        $originalParent = $this->documentService->getDocumentElement($user, $originalParentId);
        $parent = $this->documentService->getDocumentElement($user, $parentId);
        $parentPath = preg_replace(
            '@^' . $originalParent->getRealFullPath() . '@',
            $parent . '/',
            $source->getRealPath()
        );

        return $this->documentService->getDocumentElementByPath($user, $parentPath);
    }

    private function cloneChildrenWithExecutionEngine(
        UserInterface $user,
        Document $originalParent,
        Document $newParent,
        CloneData $cloneData
    ): int {
        $ids = $this->documentSearchService->getChildrenIds($originalParent->getRealFullPath(), 'asc');
        $job = new Job(
            name: Jobs::CLONE_DOCUMENTS->value,
            steps: array_map(
                static fn (int $id) => new JobStep(
                    JobSteps::DOCUMENT_CLONING->value,
                    CloneMessage::class,
                    '',
                    [self::DOCUMENT_TO_CLONE => $id]
                ),
                $ids
            ),
            environmentData: $this->getEnvironmentData($originalParent, $newParent, $cloneData),
        );

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }

    private function getEnvironmentData(
        Document $originalParent,
        Document $newParent,
        CloneData $cloneData
    ): array {
        return [
            EnvironmentVariables::ORIGINAL_PARENT_ID->value => $originalParent->getId(),
            EnvironmentVariables::PARENT_ID->value => $newParent->getId(),
            EnvironmentVariables::CLONE_DATA->value => $cloneData,
        ];
    }
}
