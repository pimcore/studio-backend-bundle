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

use JsonException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Event\PreResponse\GdprDataProviderEvent;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Event\PreResponse\GdprSearchResultEvent;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter\GdprStructuredSearchParameters;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataProvider;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResult;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprSearchResultCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\StreamedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function count;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class GdprManagerService implements GdprManagerServiceInterface
{
    use StreamedResponseTrait;

    public function __construct(
        private DataProviderLoaderInterface $loader,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableProviders(): Collection
    {
        $providers = $this->sortProviders($this->loader->getDataProviders());

        return $this->getDataProviderCollection($providers);
    }

    /**
     * {@inheritdoc}
     */
    public function search(GdprStructuredSearchParameters $request): GdprSearchResultCollection
    {
        $allResults = [];

        foreach ($request->getProviders() as $providerKey) {
            $provider = $this->loader->resolve($providerKey);

            $this->checkProviderPermission($provider);

            $results = $provider->findData($request->getSearchTerms(), $request->getFilters());

            if (!empty($results->getItems())) {
                $allResults[] = new GdprSearchResult(
                    providerKey: $providerKey,
                    results: $results->getItems(),
                    totalSubItems: $results->getTotalItems()
                );
            }
        }

        return $this->getSearchResultCollection($allResults);

    }

    /**
     * {@inheritdoc}
     */
    public function getExportData(int $id, string $providerKey): Response
    {
        $provider = $this->loader->resolve($providerKey);

        $this->checkProviderPermission($provider);

        $data = $provider->getSingleItemForDownload($id);

        return $this->createExportResponse($data, $providerKey, $id);
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
                deleteOperationId: $provider->getDeleteSwaggerOperationId(),
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
     * @param array<GdprSearchResult> $results
     */
    private function getSearchResultCollection(array $results): GdprSearchResultCollection
    {
        $collection = new GdprSearchResultCollection($results, count($results));

        $this->eventDispatcher->dispatch(
            new GdprSearchResultEvent($collection),
            GdprSearchResultEvent::EVENT_NAME
        );

        return $collection;
    }

    private function createExportResponse(mixed $data, string $providerKey, int $id): Response
    {
        // If $data is a Response (e.g., assets export), return it directly.
        // Otherwise, assume $data is an array and encode it as pretty JSON for download.
        if ($data instanceof Response) {
                return $data;
        }

        try {
            $jsonData = json_encode($data, JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(
                sprintf(
                    'JSON encode failed for "%s" (ID: %d): %s',
                    $providerKey,
                    $id,
                    $e->getMessage()
                ),
                previous: $e
            );
        }

        $filename = sprintf('gdpr-export-%s-%d.json', $providerKey, $id);
        $fileSize = strlen($jsonData);

        $headers = $this->getResponseHeaders(
            mimeType: 'application/json',
            fileSize: $fileSize,
            filename: $filename,
            contentDisposition: HttpResponseHeaders::ATTACHMENT_TYPE->value
        );

        return new StreamedResponse(
            function () use ($jsonData) {
                echo $jsonData;
            },
            HttpResponseCodes::SUCCESS->value,
            $headers
        );
    }

    /**
     * @param array<string, DataProviderInterface> $providers
     *
     * @return array<string, DataProviderInterface>
     */
    private function sortProviders(array $providers): array
    {
        uasort(
            $providers,
            static fn (DataProviderInterface $a, DataProviderInterface $b): int =>
                $b->getSortPriority() <=> $a->getSortPriority()
        );

        return $providers;
    }

    /**
     * @throws ForbiddenException
     */
    private function checkProviderPermission(DataProviderInterface $provider): void
    {
        $currentUser = $this->securityService->getCurrentUser();
        $permissions = $provider->getRequiredPermissions();

        $isGranted = false;

        foreach ($permissions as $permission) {
            if ($currentUser->isAllowed($permission)) {
                $isGranted = true;

                break;
            }
        }

        if (!$isGranted) {
            throw new ForbiddenException(
                sprintf(
                    'Not allowed for provider: %s. Required permission(s): %s',
                    $provider->getKey(),
                    implode(', ', $permissions)
                )
            );
        }
    }
}
