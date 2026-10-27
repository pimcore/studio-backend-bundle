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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Service\ExecutionEngine;

use Exception;
use Pimcore\Bundle\GenericDataIndexBundle\Service\SearchIndex\IndexQueue\SynchronousProcessingServiceInterface;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\Model\CloneDescendants;
use Pimcore\Bundle\StudioBackendBundle\Asset\Repository\CloneDescendantsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementPermissions;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class CloneService implements CloneServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private CloneDescendantsRepositoryInterface $cloneDescendantsRepository,
        private CloneJobFactoryInterface $cloneJobFactory,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
    }

    /**
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
        // Loaded before the source is cloned, so that a clone into the source itself does not clone itself again
        $descendants = $this->cloneDescendantsRepository->getDescendants($source);
        $newParent = $this->cloneElement(
            $source,
            $this->assetService->getAssetElement(
                $user,
                $parentId,
            ),
            $this->securityService->getCurrentUser()
        );

        return $this->cloneChildrenWithExecutionEngine(
            user: $user,
            originalParent: $source,
            newParent: $newParent,
            descendants: $descendants,
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

            /** @var User $user */
            return (new AssetService($user))->copyAsChild(
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
     * @throws ForbiddenException|NotFoundException
     */
    public function getNewCloneTarget(
        UserInterface $user,
        Asset $source,
        int $originalParentId,
        int $parentId,
    ): Asset {
        $originalParent = $this->assetService->getAssetElement($user, $originalParentId);
        $parent = $this->assetService->getAssetElement($user, $parentId);
        $relativePath = substr($source->getRealPath(), strlen($originalParent->getRealFullPath()));

        return $this->assetService->getAssetElementByPath(
            $user,
            rtrim($parent->getRealFullPath(), '/') . rtrim($relativePath, '/')
        );
    }

    private function cloneChildrenWithExecutionEngine(
        UserInterface $user,
        Asset $originalParent,
        Asset $newParent,
        CloneDescendants $descendants,
    ): ?int {
        $job = $this->cloneJobFactory->create($descendants, $originalParent->getId(), $newParent->getId());
        if ($job === null) {
            return null;
        }

        $jobRun = $this->jobExecutionAgent->startJobExecution(
            $job,
            $user->getId(),
            Config::CONTEXT_STOP_ON_ERROR->value
        );

        return $jobRun->getId();
    }
}
