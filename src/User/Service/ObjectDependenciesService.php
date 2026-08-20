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

use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionParameters;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\ObjectDependenciesRepositoryInterface;

/**
 * @internal
 */
final readonly class ObjectDependenciesService implements ObjectDependenciesServiceInterface
{
    public function __construct(
        private ObjectDependenciesRepositoryInterface $objectDependenciesRepository,
        private DependencyHydratorInterface $dependencyHydrator
    ) {
    }

    public function getPaginatedDependenciesForUser(int $userId, CollectionParameters $parameters): Collection
    {
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
}
