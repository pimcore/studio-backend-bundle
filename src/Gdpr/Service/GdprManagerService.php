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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Service;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Event\PreResponse\GdprDataProviderEvent;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter\GdprStructuredSearchRequest;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataProvider;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprExportJob;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprExportJobCollection;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResultCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function count;
use function sprintf;

/**
 * @internal
 */
final readonly class GdprManagerService implements GdprManagerServiceInterface
{
    public function __construct(
        private DataProviderLoaderInterface $loader,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    public function getAvailableProviders(): Collection
    {
        $providers = $this->sortProviders($this->loader->getDataProviders());

        return $this->getDataProviderCollection($providers);
    }

    public function search(GdprStructuredSearchRequest $request): GdprSearchResultCollection
    {
        $allResults = [];
        $currentUser = $this->securityService->getCurrentUser();

        foreach ($request->providers as $providerKey) {
            $provider = $this->loader->resolve($providerKey);

            $permission = $provider->getRequiredPermission();

            // Check if the current user has the required permission to access the provider
            if ($currentUser === null || !$currentUser->isAllowed($permission->value)) {
                throw new ForbiddenException(
                    sprintf(
                        'Not allowed to access the targeted provider "%s". Required permission: "%s"',
                        $providerKey,
                        $permission->value
                    )
                );
            }

            $results = $provider->findData($request->searchTerms);

            if (!empty($results)) {
                $allResults[] = new GdprSearchResult(
                    providerKey: $providerKey,
                    results: $results
                );
            }
        }

        return new GdprSearchResultCollection($allResults);
    }

    public function startBackgroundExport(GdprStructuredSearchRequest $request): GdprExportJobCollection
    {
        $jobs = [];
        $currentUser = $this->securityService->getCurrentUser();

        foreach ($request->providers as $providerKey) {
            $provider = $this->loader->resolve($providerKey);

            $permission = $provider->getRequiredPermission();
            if ($currentUser === null || !$currentUser->isAllowed($permission->value)) {
                throw new ForbiddenException("Not allowed for provider: $providerKey");
            }

            $jobId = $provider->startJobExecution($request);
            $jobs[] = new GdprExportJob($providerKey, $jobId);
        }

        return new GdprExportJobCollection($jobs);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function getExportFile(int $jobId): StreamedResponse
    {
        $currentUser = $this->securityService->getCurrentUser();

        $providers = $this->loader->getDataProviders();

        foreach ($providers as $provider) {

            $permission = $provider->getRequiredPermission();
            if ($currentUser === null || !$currentUser->isAllowed($permission->value)) {
                throw new ForbiddenException("Not allowed for provider: $provider");
            }

            if ($provider->ownsJob($jobId)) {

                return $provider->getExportFile($jobId);
            }
        }

        throw new NotFoundException('Export job with ID %d not found or access denied.', $jobId);
    }

    /**
     * @param array<string, DataProviderInterface> $providers
     *
     * @return Collection<GdprDataProvider>
     */
    private function getDataProviderCollection(array $providers): Collection
    {
        $items = [];

        foreach ($providers as $key => $provider) {
            $item = new GdprDataProvider(
                key: $key,
                label: $provider->getName(),
                columns: $provider->getAvailableColumns(),
            );

            $this->eventDispatcher->dispatch(
                new GdprDataProviderEvent($item),
                GdprDataProviderEvent::EVENT_NAME
            );

            $items[] = $item;
        }

        return new Collection(count($items), $items);
    }

    /**
     * Sorts the providers by priority.
     *
     * @param array<string, DataProviderInterface> $providers
     *
     * @return array<string, DataProviderInterface>
     */
    private function sortProviders(array $providers): array
    {
        // Higher number = Higher priority.
        uasort($providers, static fn (DataProviderInterface $a, DataProviderInterface $b): int
            => $b->getSortPriority() <=> $a->getSortPriority()
        );

        return $providers;
    }
}
