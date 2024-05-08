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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\RepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Request\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Result\ListingResult;
use Pimcore\Model\UserInterface;

final readonly class DependencyHydratorService implements DependencyHydratorServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private RepositoryInterface $repository,
        private DependencyHydratorInterface $dependencyHydrator,
    ) {
    }
    public function getHydratedDependencies(
        DependencyParameters $parameters,
        UserInterface $user
    ): ListingResult
    {
        return match ($parameters->getMode()) {
            DependencyMode::REQUIRES => $this->getHydratedRequiredDependencies($parameters),
            DependencyMode::REQUIRED_BY => $this->getHydratedRequiredByDependencies($parameters),
        };
    }

    private function hydrateDependencyCollection(array $dependencies): array
    {
        $hydratedDependencies = [];

        foreach($dependencies as $dependency) {
            $hydratedDependency = $this->dependencyHydrator->hydrate($dependency);
            if($hydratedDependency) {
                $hydratedDependencies[] = $hydratedDependency;
            }
        }

        return $hydratedDependencies;
    }

    private function getHydratedRequiredDependencies(
        DependencyParameters $parameters
    ): ListingResult {

        $dependencies = $this->repository->listRequiresDependencies(
            $parameters->getElementType(),
            $parameters->getElementId()
        );

        $dependencies = $this->hydrateDependencyCollection($dependencies);

        return new ListingResult(
            $dependencies,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $this->repository->listRequiresDependenciesTotalCount(
                $parameters->getElementType(),
                $parameters->getElementId()
            )
        );
    }

    private function getHydratedRequiredByDependencies(
        DependencyParameters $parameters
    ): ListingResult {
        $dependencies = $this->repository->listRequiredByDependencies(
            $parameters->getElementType(),
            $parameters->getElementId()
        );

        $dependencies = $this->hydrateDependencyCollection($dependencies);

        return new ListingResult(
            $dependencies,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $this->repository->listRequiredByDependenciesTotalCount(
                $parameters->getElementType(),
                $parameters->getElementId()
            )
        );
    }
}