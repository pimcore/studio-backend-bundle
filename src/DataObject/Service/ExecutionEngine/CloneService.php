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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Service\ExecutionEngine;

use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\AutomationAction\Messenger\Messages\CloneMessage;
use Pimcore\Bundle\StudioBackendBundle\DataObject\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\CloneParameters;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataObjectServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\EnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\DataObject;
use Pimcore\Model\DataObject\Service as DataObjectService;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class CloneService implements CloneServiceInterface
{
    private DataObjectService $coreDataObjectService;

    public function __construct(
        private DataObjectServiceInterface $dataObjectService,
        private DataObjectSearchServiceInterface $dataObjectSearchService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
        $this->coreDataObjectService = new DataObjectService();
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneDataObjects(
        int $sourceId,
        int $parentId,
        CloneParameters $parameters,
    ): ?int {
        $user = $this->securityService->getCurrentUser();
        $source = $this->dataObjectService->getDataObjectElement(
            $user,
            $sourceId,
        );
        $newParent = $this->cloneDataObject(
            $source,
            $this->dataObjectService->getDataObjectElement(
                $user,
                $parentId,
            ),
            $this->securityService->getCurrentUser()
        );

        if (!$parameters->isRecursive() || !$source->hasChildren()) {
            return null;
        }

        return $this->cloneChildrenWithExecutionEngine(
            user: $user,
            originalParent: $source,
            newParent: $newParent,
            updateReferences: $parameters->isUpdateReferences(),
        );

    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     */
    public function cloneDataObject(
        DataObject $source,
        DataObject $parent,
        UserInterface $user
    ): DataObject {
        if (!$parent->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing permissions on target element %s',
                    $parent->getId()
                )
            );
        }

        $this->synchronousProcessingService->enable();

        $dataObject = $this->coreDataObjectService->copyAsChild(
            $parent,
            $source,
        );

        if (!$dataObject instanceof DataObject) {
            throw new ElementSavingFailedException($source->getId(), 'Failed to clone data object');
        }

        return $dataObject;
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        DataObject $source,
        int $originalParentId,
        int $parentId,
    ): DataObject {
        $originalParent = $this->dataObjectService->getDataObjectElement($user, $originalParentId);
        $parent = $this->dataObjectService->getDataObjectElement($user, $parentId);
        $parentPath = preg_replace(
            '@^' . $originalParent->getRealFullPath() . '@',
            $parent . '/',
            $source->getRealPath()
        );

        return $this->dataObjectService->getDataObjectElementByPath($user, $parentPath);
    }

    private function cloneChildrenWithExecutionEngine(
        UserInterface $user,
        DataObject $originalParent,
        DataObject $newParent,
        bool $updateReferences
    ): int {
        $ids = $this->dataObjectSearchService->getChildrenIds($originalParent->getRealFullPath(), 'asc');
        $job = new Job(
            name: Jobs::CLONE_DATA_OBJECTS->value,
            steps: array_map(
                static fn (int $id) => new JobStep(
                    JobSteps::DATA_OBJECT_CLONING->value,
                    CloneMessage::class,
                    '',
                    [self::OBJECT_TO_CLONE => $id]
                ),
                $ids
            ),
            environmentData: [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => $originalParent->getId(),
                EnvironmentVariables::PARENT_ID->value => $newParent->getId(),
                EnvironmentVariables::UPDATE_REFERENCES->value => $updateReferences,
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
