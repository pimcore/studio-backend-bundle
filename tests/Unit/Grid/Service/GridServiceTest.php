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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Grid\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\LocalizedFieldResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchResult;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Grid\GridSearchInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnCollectorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnDefinitionLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnResolverLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridService;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class GridServiceTest extends Unit
{
    /**
     * A stale ElasticSearch index can reference an object that no longer exists in the
     * database (sync lag). A single missing element must not break the whole grid: it
     * should be skipped and logged, and the remaining rows still returned.
     *
     * @see https://github.com/pimcore/studio-backend-bundle/issues/1894
     */
    public function testGetDataObjectGridSkipsElementsMissingFromDatabase(): void
    {
        $missingId = 534;
        $existingId = 100;

        $searchResult = new DataObjectSearchResult(
            items: [
                $this->makeEmpty(StudioElementInterface::class, ['getId' => $missingId]),
                $this->makeEmpty(StudioElementInterface::class, ['getId' => $existingId]),
            ],
            currentPage: 1,
            pageSize: 10,
            totalItems: 2,
        );

        $existingObject = $this->makeEmpty(AbstractObject::class);
        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => static fn (string $type, int|string $id): ?AbstractObject =>
                $id === $missingId ? null : $existingObject,
        ]);

        $service = $this->createService(
            gridSearch: $this->makeEmpty(GridSearchInterface::class, [
                'searchDataObjects' => $searchResult,
            ]),
            serviceResolver: $serviceResolver,
            // The missing element must produce exactly one warning.
            logger: $this->makeEmpty(LoggerInterface::class, ['warning' => Expected::once()]),
        );

        // Empty column set keeps the test focused on the element-loading behaviour.
        $gridParameter = new GridParameter(folderId: 1, columns: [], filters: null);

        $result = $service->getDataObjectGrid($gridParameter, null);

        // Only the existing element is returned; the missing one is skipped, not fatal.
        $this->assertCount(1, $result->getItems());
        // The search-reported total is intentionally left untouched.
        $this->assertSame(2, $result->getTotalItems());
    }

    private function createService(
        ?GridSearchInterface $gridSearch = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?LoggerInterface $logger = null,
    ): GridService {
        return new GridService(
            $this->makeEmpty(ColumnDefinitionLoaderInterface::class),
            $this->makeEmpty(ColumnResolverLoaderInterface::class),
            $this->makeEmpty(ColumnCollectorLoaderInterface::class),
            $gridSearch ?? $this->makeEmpty(GridSearchInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $logger ?? $this->makeEmpty(LoggerInterface::class),
        );
    }
}
