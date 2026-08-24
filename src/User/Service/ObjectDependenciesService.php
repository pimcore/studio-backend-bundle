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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidFilterException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\ObjectDependenciesRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ObjectDependencies;
use function sprintf;

/**
 * @internal
 */
final readonly class ObjectDependenciesService implements ObjectDependenciesServiceInterface
{
    public function __construct(
        private ObjectDependenciesRepositoryInterface $objectDependenciesRepository,
        private DependencyHydratorInterface $dependencyHydrator,
        private UserRepositoryInterface $userRepository,
        private SecurityServiceInterface $securityService
    ) {
    }

    public function getPaginatedDependenciesForUser(int $userId, CollectionParameters $parameters): Collection
    {
        if ($parameters->getPageSize() > self::MAX_PAGE_SIZE) {
            throw new InvalidFilterException(sprintf('pageSize must not exceed %d', self::MAX_PAGE_SIZE));
        }

        $targetUser = $this->userRepository->getUserById($userId);

        if ($targetUser->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admins can view other admins');
        }

        $result = $this->objectDependenciesRepository->getObjectsReferencingUser(
            $userId,
            $parameters->getOffset(),
            $parameters->getPageSize()
        );

        $dependencies = [];
        foreach ($result['items'] as $object) {
            if ($object->isAllowed('list')) {
                $dependencies[] = $this->dependencyHydrator->hydrate($object);
            }
        }

        return new Collection($result['totalItems'], $dependencies);
    }

    public function getPreviewForUser(int $userId, int $previewSize): ObjectDependencies
    {
        $result = $this->objectDependenciesRepository->getObjectsReferencingUser($userId, 0, $previewSize);

        $dependencies = [];
        $hasHidden = false;
        foreach ($result['items'] as $object) {
            if ($object->isAllowed('list')) {
                $dependencies[] = $this->dependencyHydrator->hydrate($object);

                continue;
            }

            $hasHidden = true;
        }

        return new ObjectDependencies($dependencies, $hasHidden, $result['totalItems']);
    }
}
