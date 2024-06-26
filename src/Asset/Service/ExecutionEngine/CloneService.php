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
use Pimcore\Bundle\StudioBackendBundle\Asset\ExecutionEngine\AutomationAction\Messenger\Messages\AssetCopyMessage;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Config;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Util\Jobs;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\Asset\CloneEnvironmentVariables;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\Element\ElementDescriptor;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final readonly class CloneService implements CloneServiceInterface
{
    public function __construct(
        private AssetServiceInterface $assetService,
        private AssetSearchServiceInterface $assetSearchService,
        private JobExecutionAgentInterface $jobExecutionAgent,
        private SecurityServiceInterface $securityService,
        private SynchronousProcessingServiceInterface $synchronousProcessingService
    ) {
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

        try {
            $this->synchronousProcessingService->enable();

            return (new AssetService())->copyAsChild(
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
                new JobStep(Jobs::CLONE_ASSETS->value, AssetCopyMessage::class, '', []),
            ],
            selectedElements: array_map(
                static fn (int $id) => new ElementDescriptor(
                    ElementTypes::TYPE_ASSET,
                    $id
                ),
                $ids
            ),
            environmentData: [
                CloneEnvironmentVariables::ORIGINAL_PARENT_ID->value => $originalParent->getId(),
                CloneEnvironmentVariables::PARENT_ID->value => $newParent->getId(),
            ]
        );
        $jobRun = $this->jobExecutionAgent->startJobExecution($job, $user->getId(), Config::CONTEXT->value);

        return $jobRun->getId();
    }
}
