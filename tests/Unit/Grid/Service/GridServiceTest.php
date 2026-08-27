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
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\ColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Column\StudioElementColumnResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Column;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnData;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnCollectorLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnDefinitionLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnResolverLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridService;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\WorkflowPermissionMergerInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Util\Collection\ColumnCollection;
use Pimcore\Bundle\StudioBackendBundle\Response\Element;
use Pimcore\Bundle\StudioBackendBundle\Response\ElementIcon;
use Pimcore\Bundle\StudioBackendBundle\Response\StudioElementInterface;
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowPermissionsAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
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

    /**
     * A row whose element has no workflow with permissions must neither load the core
     * element nor invoke the workflow permission merger; the user permissions pass through.
     */
    public function testWorkflowMergeIsSkippedWithoutWorkflowPermissions(): void
    {
        $permissions = new Permissions();

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::never(),
            ]),
            workflowPermissionMerger: $this->makeEmpty(WorkflowPermissionMergerInterface::class, [
                'mergeWorkflowPermissions' => Expected::never(),
            ]),
            columnResolverLoader: $this->createColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createTestColumnCollection(),
            $this->createStudioElement($permissions, false),
            ElementTypes::TYPE_ASSET,
            1
        );

        $this->assertSame($permissions, $data['permissions']);
    }

    /**
     * A row whose element has a workflow with permissions must load the core element once,
     * invoke the merger once, and expose the merged permissions on the row payload.
     */
    public function testWorkflowMergeIsAppliedForWorkflowElement(): void
    {
        $userPermissions = new Permissions();
        $mergedPermissions = new Permissions(delete: false);

        $service = $this->createService(
            serviceResolver: $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => Expected::once($this->makeEmpty(AbstractObject::class)),
            ]),
            workflowPermissionMerger: $this->makeEmpty(WorkflowPermissionMergerInterface::class, [
                'mergeWorkflowPermissions' => Expected::once($mergedPermissions),
            ]),
            columnResolverLoader: $this->createColumnResolverLoader(),
        );

        $data = $service->getGridDataForElement(
            $this->createTestColumnCollection(),
            $this->createStudioElement($userPermissions, true),
            ElementTypes::TYPE_ASSET,
            1
        );

        $this->assertSame($mergedPermissions, $data['permissions']);
    }

    private function createStudioElement(Permissions $permissions, bool $hasWorkflowWithPermissions): Element
    {
        return new class($permissions, $hasWorkflowWithPermissions) extends Element implements WorkflowPermissionsAwareInterface {
            public function __construct(
                private readonly Permissions $studioPermissions,
                private readonly bool $workflowFlag,
            ) {
                parent::__construct(
                    1,
                    1,
                    '/test',
                    new ElementIcon('name', 'pimcore_icon'),
                    1,
                    null,
                    null,
                    false,
                    null,
                    null,
                    ElementTypes::TYPE_ASSET
                );
            }

            public function getPermissions(): Permissions
            {
                return $this->studioPermissions;
            }

            public function getHasWorkflowWithPermissions(): bool
            {
                return $this->workflowFlag;
            }
        };
    }

    private function createTestColumnCollection(): ColumnCollection
    {
        return new ColumnCollection([
            new Column(
                key: 'id',
                locale: null,
                type: 'test.type',
                group: null,
                config: []
            ),
        ]);
    }

    private function createColumnResolverLoader(): ColumnResolverLoaderInterface
    {
        $resolver = new class() implements ColumnResolverInterface, StudioElementColumnResolverInterface {
            public function getType(): string
            {
                return 'test.type';
            }

            public function supportedElementTypes(): array
            {
                return [ElementTypes::TYPE_ASSET];
            }

            public function resolveForStudioElement(Column $column, StudioElementInterface $element): ColumnData
            {
                return new ColumnData($column->getKey(), $column->getLocale(), null, 'input');
            }
        };

        return $this->makeEmpty(ColumnResolverLoaderInterface::class, [
            'loadColumnResolvers' => ['test.type' => $resolver],
        ]);
    }

    private function createService(
        ?GridSearchInterface $gridSearch = null,
        ?ServiceResolverInterface $serviceResolver = null,
        ?LoggerInterface $logger = null,
        ?WorkflowPermissionMergerInterface $workflowPermissionMerger = null,
        ?ColumnResolverLoaderInterface $columnResolverLoader = null,
    ): GridService {
        return new GridService(
            $this->makeEmpty(ColumnDefinitionLoaderInterface::class),
            $columnResolverLoader ?? $this->makeEmpty(ColumnResolverLoaderInterface::class),
            $this->makeEmpty(ColumnCollectorLoaderInterface::class),
            $gridSearch ?? $this->makeEmpty(GridSearchInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class, [
                'dispatch' => static fn (object $event): object => $event,
            ]),
            $this->makeEmpty(SecurityServiceInterface::class),
            $serviceResolver ?? $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(ClassDefinitionResolverInterface::class),
            $this->makeEmpty(LocalizedFieldResolverInterface::class),
            $workflowPermissionMerger ?? $this->makeEmpty(WorkflowPermissionMergerInterface::class),
            $logger ?? $this->makeEmpty(LoggerInterface::class),
        );
    }
}
