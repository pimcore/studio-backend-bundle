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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Entity\Perspective\UserPerspectiveData;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Repository\PerspectiveConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\PerspectiveConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\PerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\PerspectiveRepositoryInterface;
use Pimcore\Model\User\UserRoleInterface;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class UserPerspectiveService implements UserPerspectiveServiceInterface
{
    public function __construct(
        private PerspectiveRepositoryInterface $repository,
        private PerspectiveConfigRepositoryInterface $configRepository,
        private PerspectiveServiceInterface $perspectiveService,
    ) {
    }

    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function getAllowedPerspectives(UserInterface $user): array
    {
        $allowedPerspectives = [];
        $userPerspectives = $this->getAllUserPerspectives($user);
        if (empty($userPerspectives)) {
            return $this->getAllPerspectives();
        }

        foreach ($userPerspectives as $userPerspective) {
            $config = $this->configRepository->getConfiguration($userPerspective);
            $allowedPerspectives[] = $this->perspectiveService->hydrateListEntry($config);
        }

        return $allowedPerspectives;
    }

    /**
     * @throws NotFoundException|NotWriteableException
     *
     * @return PerspectiveConfig[]
     */
    public function getConfigPerspectives(UserInterface|UserRoleInterface $user): array
    {
        $allowedPerspectives = [];
        $userPerspectives = $this->repository->listUserPerspectives($user->getId());

        foreach ($userPerspectives as $userPerspective) {
            $config = $this->configRepository->getConfiguration($userPerspective);
            $allowedPerspectives[] = $this->perspectiveService->hydrateListEntry($config);
        }

        return $allowedPerspectives;
    }

    public function getActivePerspective(UserInterface $user): string
    {
        $activePerspective = $this->repository->getUserActivePerspective($user->getId());
        $userPerspectives = $this->getAllUserPerspectives($user);
        if (empty($userPerspectives)) {
            $userPerspectives = $this->getAllPerspectiveIds();
        }

        $userPerspectivesSet = array_flip($userPerspectives);
        if ($activePerspective !== null && isset($userPerspectivesSet[$activePerspective])) {
            return $activePerspective;
        }

        return $userPerspectives[0];
    }

    /**
     * @throws NotFoundException|NotWriteableException
     */
    public function updatePerspectives(
        array $perspectivesToSet,
        UserInterface|UserRoleInterface $user
    ): void {
        foreach ($perspectivesToSet as $perspectiveId) {
            $this->configRepository->getConfiguration($perspectiveId);
        }
        $userPerspectiveData = $this->getPerspectiveData($user);
        $userPerspectiveData->setPerspectives(implode(',', $perspectivesToSet));
        $this->repository->update($userPerspectiveData);
    }

    /**
     * @throws ForbiddenException|NotFoundException|NotWriteableException
     */
    public function updateActivePerspective(string $perspectiveId, UserInterface $user): void
    {
        $this->configRepository->getConfiguration($perspectiveId);
        $this->validatePerspectiveAccess($user, $perspectiveId);
        $userPerspectiveData = $this->getPerspectiveData($user);
        $userPerspectiveData->setActivePerspective($perspectiveId);
        $this->repository->update($userPerspectiveData);
    }

    /**
     * @throws ForbiddenException
     */
    public function validatePerspectiveAccess(UserInterface $user, string $perspectiveId): void
    {
        $userPerspectives = $this->getAllUserPerspectives($user);
        if (empty($userPerspectives)) {
            $userPerspectives = $this->getAllPerspectiveIds();
        }
        $userPerspectivesSet = array_flip($userPerspectives);
        if (!isset($userPerspectivesSet[$perspectiveId])) {
            throw new ForbiddenException(
                sprintf('User does not have access to perspective with ID: %s', $perspectiveId)
            );
        }
    }

    private function getAllUserPerspectives(UserInterface|UserRoleInterface $user): array
    {
        $userPerspectives = $this->repository->listUserPerspectives($user->getId());
        if (!$user instanceof UserInterface) {
            return $userPerspectives;
        }

        $roles = $user->getRoles();
        foreach ($roles as $roleId) {
            $rolePerspectives = $this->repository->listUserPerspectives($roleId);
            $userPerspectives = [...$userPerspectives, ...$rolePerspectives];
        }

        return array_values(array_unique($userPerspectives));
    }

    private function getAllPerspectiveIds(): array
    {
        return array_map(static fn ($perspective) => $perspective->getId(), $this->getAllPerspectives());
    }

    private function getAllPerspectives(): array
    {
        $defaultPerspective = $this->configRepository->getConfiguration(Perspectives::DEFAULT_ID->value);
        $allowedPerspectives[] = $this->perspectiveService->hydrateListEntry($defaultPerspective);

        return array_merge($allowedPerspectives, $this->perspectiveService->listConfigurations());
    }

    private function getPerspectiveData(UserInterface|UserRoleInterface $user): UserPerspectiveData
    {
        $userPerspectiveData = $this->repository->getByUser($user->getId());
        if ($userPerspectiveData === null) {
            $userPerspectiveData = new UserPerspectiveData();
            $userPerspectiveData->setUser($user->getId());
        }

        return $userPerspectiveData;
    }
}
