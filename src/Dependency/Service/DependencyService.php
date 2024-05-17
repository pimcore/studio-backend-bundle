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

use Pimcore\Bundle\StudioBackendBundle\Dependency\Event\DependencyEvent;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Repository\DependencyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Request\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Result\Collection;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\ElementProviderTrait;
use Pimcore\Model\UserInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class DependencyService implements DependencyServiceInterface
{
    use ElementProviderTrait;

    public function __construct(
        private DependencyRepositoryInterface $dependencyRepository,
        private DependencyHydratorInterface $dependencyHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }
    public function getDependencies(
        DependencyParameters $parameters,
        UserInterface $user
    ): Collection
    {
        return match ($parameters->getMode()) {
            DependencyMode::REQUIRES => $this->getRequiredDependencies($parameters),
            DependencyMode::REQUIRED_BY => $this->getRequiredByDependencies($parameters),
        };
    }

    private function getDependencyCollection(array $dependencies): array
    {
        $hydratedDependencies = [];

        foreach($dependencies as $dependency) {
            $dependency = $this->dependencyHydrator->hydrate($dependency);
            if($dependency) {
                $this->eventDispatcher->dispatch(
                    new DependencyEvent($dependency),
                    DependencyEvent::EVENT_NAME
                );
                $hydratedDependencies[] = $dependency;
            }
        }

        return $hydratedDependencies;
    }

    private function getRequiredDependencies(
        DependencyParameters $parameters
    ): Collection {

        $dependencies = $this->dependencyRepository->listRequiresDependencies(
            $parameters->getElementType(),
            $parameters->getElementId()
        );

        $dependencies = $this->getDependencyCollection($dependencies);

        return new Collection(
            $dependencies,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $this->dependencyRepository->listRequiresDependenciesTotalCount(
                $parameters->getElementType(),
                $parameters->getElementId()
            )
        );
    }

    private function getRequiredByDependencies(
        DependencyParameters $parameters
    ): Collection {
        $dependencies = $this->dependencyRepository->listRequiredByDependencies(
            $parameters->getElementType(),
            $parameters->getElementId()
        );

        $dependencies = $this->getDependencyCollection($dependencies);

        return new Collection(
            $dependencies,
            $parameters->getPage(),
            $parameters->getPageSize(),
            $this->dependencyRepository->listRequiredByDependenciesTotalCount(
                $parameters->getElementType(),
                $parameters->getElementId()
            )
        );
    }
}