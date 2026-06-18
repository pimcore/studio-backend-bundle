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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Gdpr\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Provider\DataProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Schema\GdprDataRow;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\DataProviderLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerService;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class GdprManagerServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testGetAvailableProvidersSortsByPriorityDescending(): void
    {
        $loader = $this->makeEmpty(DataProviderLoaderInterface::class, [
            'getDataProviders' => [
                'low' => $this->createProvider('low', 'Low', 1),
                'high' => $this->createProvider('high', 'High', 10),
                'mid' => $this->createProvider('mid', 'Mid', 5),
            ],
        ]);

        $service = $this->createService(loader: $loader);

        $result = $service->getAvailableProviders();
        $items = $result->getItems();

        $this->assertSame(3, $result->getTotalItems());
        $this->assertSame(['high', 'mid', 'low'], array_map(static fn ($item) => $item->getKey(), $items));
        $this->assertSame(['High', 'Mid', 'Low'], array_map(static fn ($item) => $item->getLabel(), $items));
    }

    /**
     * @throws Exception
     */
    public function testGetAvailableProvidersDispatchesEventForEachProvider(): void
    {
        $loader = $this->makeEmpty(DataProviderLoaderInterface::class, [
            'getDataProviders' => [
                'a' => $this->createProvider('a', 'A', 1),
                'b' => $this->createProvider('b', 'B', 2),
            ],
        ]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::exactly(2, static fn (object $event): object => $event),
        ]);

        $service = $this->createService(loader: $loader, eventDispatcher: $eventDispatcher);

        $service->getAvailableProviders();
    }

    /**
     * @throws Exception
     */
    public function testSearchDispatchesEventForEachResultItem(): void
    {
        $results = new Collection(2, [
            new GdprDataRow(['id' => 1]),
            new GdprDataRow(['id' => 2]),
        ]);

        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'findData' => $results,
        ]);

        $loader = $this->makeEmpty(DataProviderLoaderInterface::class, [
            'resolve' => $provider,
        ]);

        $eventDispatcher = $this->makeEmpty(EventDispatcherInterface::class, [
            'dispatch' => Expected::exactly(2, static fn (object $event): object => $event),
        ]);

        $service = $this->createService(loader: $loader, eventDispatcher: $eventDispatcher);

        $result = $service->search(new CollectionFilterParameter(), 'data_objects');

        $this->assertSame($results, $result);
    }

    /**
     * @throws Exception
     */
    public function testSearchUsesEmptyFilterParameterWhenNoneProvided(): void
    {
        $capturedFilter = null;

        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'findData' => function (FilterParameter $filter) use (&$capturedFilter) {
                $capturedFilter = $filter;

                return new Collection(0, []);
            },
        ]);

        $loader = $this->makeEmpty(DataProviderLoaderInterface::class, [
            'resolve' => $provider,
        ]);

        $service = $this->createService(loader: $loader);

        $service->search(new CollectionFilterParameter(), 'data_objects');

        $this->assertInstanceOf(FilterParameter::class, $capturedFilter);
    }

    /**
     * @throws Exception
     */
    public function testGetExportDataThrowsForbiddenWhenUserLacksPermission(): void
    {
        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'getRequiredPermissions' => ['gdpr_data_objects'],
            'getKey' => 'data_objects',
            'getSingleItemForDownload' => Expected::never(),
        ]);

        $service = $this->createExportService($provider, false);

        $this->expectException(ForbiddenException::class);
        $service->getExportData(7, 'data_objects');
    }

    /**
     * @throws Exception
     */
    public function testGetExportDataGrantedWhenUserHasAnyOfRequiredPermissions(): void
    {
        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'getRequiredPermissions' => ['denied_permission', 'granted_permission'],
            'getKey' => 'data_objects',
            'getSingleItemForDownload' => ['id' => 7],
        ]);

        $user = $this->makeEmpty(UserInterface::class, [
            'isAllowed' => static fn (string $key): bool => $key === 'granted_permission',
        ]);

        $service = $this->createService(
            loader: $this->makeEmpty(DataProviderLoaderInterface::class, ['resolve' => $provider]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, ['getCurrentUser' => $user]),
        );

        $response = $service->getExportData(7, 'data_objects');

        $this->assertInstanceOf(StreamedResponse::class, $response);
    }

    /**
     * @throws Exception
     */
    public function testGetExportDataReturnsResponse(): void
    {
        $providerResponse = new Response('asset-binary');

        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'getRequiredPermissions' => ['gdpr_assets'],
            'getSingleItemForDownload' => $providerResponse,
        ]);

        $service = $this->createExportService($provider, true);

        $result = $service->getExportData(42, 'assets');

        $this->assertSame($providerResponse, $result);
    }

    /**
     * @throws Exception
     */
    public function testGetExportDataResponse(): void
    {
        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'getRequiredPermissions' => ['gdpr_data_objects'],
            'getSingleItemForDownload' => ['id' => 7, 'fullPath' => '/customers/jane'],
        ]);

        $service = $this->createExportService($provider, true);

        $response = $service->getExportData(7, 'data_objects');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'gdpr-export-data_objects-7.json',
            (string) $response->headers->get('Content-Disposition')
        );
    }

    /**
     * @throws Exception
     */
    public function testGetExportDataThrow(): void
    {
        $provider = $this->makeEmpty(DataProviderInterface::class, [
            'getRequiredPermissions' => ['gdpr_data_objects'],
            'getSingleItemForDownload' => ['invalid' => NAN],
        ]);

        $service = $this->createExportService($provider, true);

        $this->expectException(InvalidArgumentException::class);
        $service->getExportData(7, 'data_objects');
    }

    /**
     * @throws Exception
     */
    private function createProvider(string $key, string $name, int $sortPriority): DataProviderInterface
    {
        return $this->makeEmpty(DataProviderInterface::class, [
            'getKey' => $key,
            'getName' => $name,
            'getSortPriority' => $sortPriority,
        ]);
    }

    /**
     * @throws Exception
     */
    private function createExportService(DataProviderInterface $provider, bool $isAllowed): GdprManagerService
    {
        return $this->createService(
            loader: $this->makeEmpty(DataProviderLoaderInterface::class, ['resolve' => $provider]),
            securityService: $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAllowed' => $isAllowed]),
            ]),
        );
    }

    /**
     * @throws Exception
     */
    private function createService(
        ?DataProviderLoaderInterface $loader = null,
        ?EventDispatcherInterface $eventDispatcher = null,
        ?SecurityServiceInterface $securityService = null,
    ): GdprManagerService {
        return new GdprManagerService(
            $loader ?? $this->makeEmpty(DataProviderLoaderInterface::class),
            $eventDispatcher ?? $this->makeEmpty(EventDispatcherInterface::class),
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class),
        );
    }
}
