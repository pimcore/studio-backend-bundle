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

namespace Pimcore\Bundle\StudioBackendBundle\Dependency\Service;

use Pimcore\Bundle\StudioBackendBundle\Dependency\Event\DependencyEvent;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Hydrator\DependencyHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\MappedParameter\DependencyParameters;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Repository\DependencyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Dependency\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\ElementProviderTrait;
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
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): Collection {
        return match ($parameters->getMode()) {
            DependencyMode::REQUIRES => $this->getRequiredDependencies($elementParameters, $parameters, $user),
            DependencyMode::REQUIRED_BY => $this->getRequiredByDependencies($elementParameters, $parameters, $user),
        };
    }

    private function getDependencyCollection(array $dependencies): array
    {
        $hydratedDependencies = [];

        foreach ($dependencies as $dependency) {
            $result = $this->dependencyHydrator->hydrate($dependency);

            $this->eventDispatcher->dispatch(
                new DependencyEvent($result),
                DependencyEvent::EVENT_NAME
            );
            $hydratedDependencies[] = $result;

        }

        return $hydratedDependencies;
    }

    private function getRequiredDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): Collection {

        $result = $this->dependencyRepository->listRequiresDependencies(
            $elementParameters,
            $parameters,
            $user
        );

        $dependencies = $this->getDependencyCollection($result->getItems());

        return new Collection(
            $dependencies,
            $result->getPagination()->getPage(),
            $result->getPagination()->getPageSize(),
            $result->getPagination()->getTotalItems()
        );
    }

    private function getRequiredByDependencies(
        ElementParameters $elementParameters,
        DependencyParameters $parameters,
        UserInterface $user
    ): Collection {

        $result = $this->dependencyRepository->listRequiredByDependencies(
            $elementParameters,
            $parameters,
            $user
        );

        $dependencies = $this->getDependencyCollection($result->getItems());

        return new Collection(
            $dependencies,
            $result->getPagination()->getPage(),
            $result->getPagination()->getPageSize(),
            $result->getPagination()->getTotalItems()
        );
    }
}
