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

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\Job;
use Pimcore\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCloneMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Util\JobSteps;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
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
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final class CloneService implements CloneServiceInterface
{
    private AssetService $coreAssetService;

    public function __construct(
        private readonly AssetServiceInterface $assetService,
        private readonly AssetSearchServiceInterface $assetSearchService,
        private readonly JobExecutionAgentInterface $jobExecutionAgent,
        private readonly SecurityServiceInterface $securityService,
        private readonly SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
        $this->coreAssetService = new AssetService();
    }

    /**
     * @throws AccessDeniedException
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    public function cloneAssetRecursively(
        int $sourceId,
        int $parentId
    ): ?int {
        $user = $this->securityService->getCurrentUser();
        $source = $this->assetService->getAssetElement(
            $user,
            $sourceId,
        );
        $newParent = $this->cloneElement(
            $source,
            $this->assetService->getAssetElement(
                $user,
                $parentId,
            ),
            $this->securityService->getCurrentUser()
        );

        if (!$source->hasChildren()) {
            return null;
        }

        return $this->cloneChildrenWithExecutionEngine(
            user: $user,
            originalParent: $source,
            newParent: $newParent,
        );

    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     */
    public function cloneElement(
        Asset $source,
        Asset $parent,
        UserInterface $user
    ): Asset {
        if (!$parent->isAllowed(ElementPermissions::CREATE_PERMISSION)) {
            throw new ForbiddenException(
                sprintf(
                    'Missing permissions on target element %s',
                    $parent->getId()
                )
            );
        }

        if (!$parent instanceof Folder) {
            throw new ElementSavingFailedException(
                null,
                sprintf('Invalid parent type (%s)', $parent->getType())
            );
        }

        try {
            $this->synchronousProcessingService->enable();

            return $this->coreAssetService->copyAsChild(
                $parent,
                $source,
            );
        } catch (Exception $e) {
            throw new ElementSavingFailedException(
                null,
                $e->getMessage()
            );
        }
    }

    /**
     * @throws AccessDeniedException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        Asset $source,
        int $originalParentId,
        int $parentId,
    ): Asset {
        $originalParent = $this->assetService->getAssetElement($user, $originalParentId);
        $parent = $this->assetService->getAssetElement($user, $parentId);
        $parentPath = preg_replace(
            '@^' . $originalParent->getRealFullPath() . '@',
            $parent . '/',
            $source->getRealPath()
        );

        return $this->assetService->getAssetElementByPath($user, $parentPath);
    }

    private function cloneChildrenWithExecutionEngine(
        UserInterface $user,
        Asset $originalParent,
        Asset $newParent,
    ): int {
        $ids = $this->assetSearchService->getChildrenIds($originalParent->getRealFullPath(), 'asc');
        $job = new Job(
            name: Jobs::CLONE_ASSETS->value,
            steps: [
                new JobStep(JobSteps::ASSET_CLONING->value, AssetCloneMessage::class, '', []),
            ],
            selectedElements: array_map(
                static fn (int $id) => new ElementDescriptor(
                    ElementTypes::TYPE_ASSET,
                    $id
                ),
                $ids
            ),
            environmentData: [
                EnvironmentVariables::ORIGINAL_PARENT_ID->value => $originalParent->getId(),
                EnvironmentVariables::PARENT_ID->value => $newParent->getId(),
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
