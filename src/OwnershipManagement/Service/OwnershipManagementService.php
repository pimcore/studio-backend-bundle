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

namespace Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Event\PreResponse\ConfigurationTypeEvent;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Event\PreResponse\OwnershipConfigurationEvent;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Hydrator\ConfigurationTypeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\DeleteParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\MappedParameter\ReassignOwnerParameter;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Provider\OwnershipProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipListQuery;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Query\OwnershipSort;
use Pimcore\Bundle\StudioBackendBundle\OwnershipManagement\Util\Trait\OwnershipFilterTrait;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserServiceInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class OwnershipManagementService implements OwnershipManagementServiceInterface
{
    use OwnershipFilterTrait;

    public function __construct(
        private ProviderLoaderInterface $providerLoader,
        private ConfigurationTypeHydratorInterface $configurationTypeHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private UserServiceInterface $userService,
        private JobServiceInterface $jobService,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function getAvailableTypes(): Collection
    {
        $this->ensureCurrentUserIsAdmin();

        $items = [];
        foreach ($this->sortProviders($this->providerLoader->getProviders()) as $provider) {
            $type = $this->configurationTypeHydrator->hydrate($provider);

            $this->eventDispatcher->dispatch(
                new ConfigurationTypeEvent($type),
                ConfigurationTypeEvent::EVENT_NAME
            );

            $items[] = $type;
        }

        return new Collection(count($items), $items);
    }

    public function listConfigurations(string $type, CollectionFilterParameter $parameters): Collection
    {
        $this->ensureCurrentUserIsAdmin();

        $collection = $this->providerLoader->resolve($type)->listConfigurations(
            $this->createListQuery($parameters->getFilters() ?? new FilterParameter())
        );

        foreach ($collection->getItems() as $item) {
            $this->eventDispatcher->dispatch(
                new OwnershipConfigurationEvent($item),
                OwnershipConfigurationEvent::EVENT_NAME
            );
        }

        return $collection;
    }

    public function reassignOwner(string $type, ReassignOwnerParameter $parameter): ?int
    {
        $this->ensureCurrentUserIsAdmin();
        $this->validateOwner($parameter->getNewOwnerId());
        $provider = $this->providerLoader->resolve($type);
        $ids = $parameter->getIds();

        if (count($ids) === 1) {
            $provider->reassignOwner($ids, $parameter->getNewOwnerId());

            return null;
        }

        return $this->jobService->createReassignOwnerJob($type, $ids, $parameter->getNewOwnerId());
    }

    public function delete(string $type, DeleteParameter $parameter): ?int
    {
        $this->ensureCurrentUserIsAdmin();
        $provider = $this->providerLoader->resolve($type);
        $ids = $parameter->getIds();

        if (count($ids) === 1) {
            $provider->delete($ids);

            return null;
        }

        return $this->jobService->createDeleteJob($type, $ids);
    }

    /**
     * Maps the internal request filter into the public OwnershipListQuery handed to providers, so they
     * never depend on the internal FilterParameter representation.
     *
     * @throws InvalidArgumentException
     */
    private function createListQuery(FilterParameter $filter): OwnershipListQuery
    {
        $sortBy = [];
        foreach ($filter->getSortFilters() as $sortFilter) {
            $sortBy[] = new OwnershipSort($sortFilter->getKey(), $sortFilter->getDirection());
        }

        return new OwnershipListQuery(
            $filter->getStart(),
            $filter->getPageSize(),
            $this->getSearchTerm($filter),
            $this->includeDeletedOwners($filter),
            $sortBy,
        );
    }

    /**
     * @throws ForbiddenException
     */
    private function ensureCurrentUserIsAdmin(): void
    {
        if (!$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Ownership management is restricted to admin users.');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateOwner(int $newOwnerId): void
    {
        if ($this->userService->getUserNameById($newOwnerId) === null) {
            throw new InvalidArgumentException(
                sprintf('Cannot reassign ownership: user with ID "%d" does not exist.', $newOwnerId)
            );
        }
    }

    /**
     * @param array<string, OwnershipProviderInterface> $providers
     *
     * @return array<string, OwnershipProviderInterface>
     */
    private function sortProviders(array $providers): array
    {
        uasort(
            $providers,
            static fn (OwnershipProviderInterface $a, OwnershipProviderInterface $b): int =>
                $b->getSortPriority() <=> $a->getSortPriority()
        );

        return $providers;
    }
}
