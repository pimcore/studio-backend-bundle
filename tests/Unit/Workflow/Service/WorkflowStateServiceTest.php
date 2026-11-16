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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Workflow\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowStatusHydrator;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowStateService;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use stdClass;
use function array_keys;
use function array_map;

/**
 * @internal
 */
final class WorkflowStateServiceTest extends Unit
{
    public function testAppliesVisibleStatesInConfigurationOrder(): void
    {
        $element = $this->createElement(12);
        $service = $this->createService(
            [['cid' => 12, 'workflow' => 'enrichment', 'place' => 'done_images,edit_text,hidden']],
            ['enrichment' => [
                'edit_text' => ['label' => 'Edit Text', 'color' => '#ffa500', 'visibleInHeader' => true],
                'hidden' => ['label' => 'Hidden', 'visibleInHeader' => false],
                'done_images' => [
                    'label' => 'Done Images',
                    'color' => '#008000',
                    'colorInverted' => true,
                    'visibleInHeader' => true,
                ],
                'todo' => ['label' => 'ToDo', 'visibleInHeader' => true],
            ]]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $states = $element->getWorkflowStates();
        $this->assertSame(['edit_text', 'done_images'], $this->getTitles($states));
        $this->assertSame('Edit Text', $states[0]->getLabel());
        $this->assertSame('#ffa500', $states[0]->getColor());
        $this->assertFalse($states[0]->getColorInverted());
        $this->assertTrue($states[1]->getColorInverted());
    }

    public function testOrdersWorkflowsByPriorityLikeTheDetailView(): void
    {
        $element = $this->createElement(7);
        $service = $this->createService(
            [
                ['cid' => 7, 'workflow' => 'alpha', 'place' => 'open'],
                ['cid' => 7, 'workflow' => 'zeta', 'place' => 'review'],
            ],
            [
                'zeta' => ['review' => ['label' => 'Review', 'visibleInHeader' => true]],
                'alpha' => ['open' => ['label' => 'Open', 'visibleInHeader' => true]],
            ]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_ASSET);

        $this->assertSame(['review', 'open'], $this->getTitles($element->getWorkflowStates()));
    }

    public function testUsesThePlaceConfigOfEachRowsWorkflow(): void
    {
        $element = $this->createElement(7);
        $service = $this->createService(
            [
                ['cid' => 7, 'workflow' => 'first', 'place' => 'open'],
                ['cid' => 7, 'workflow' => 'second', 'place' => 'open'],
            ],
            [
                'first' => ['open' => ['label' => 'First open', 'visibleInHeader' => true]],
                'second' => ['open' => ['label' => 'Second open', 'visibleInHeader' => true]],
            ]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $labels = array_map(
            static fn (WorkflowStatus $state): string => $state->getLabel(),
            $element->getWorkflowStates()
        );
        $this->assertSame(['First open', 'Second open'], $labels);
    }

    public function testFetchesAllElementsWithOneLookup(): void
    {
        $first = $this->createElement(1);
        $second = $this->createElement(2);
        $repository = $this->makeEmpty(WorkflowElementsRepositoryInterface::class, [
            'fetchPlacesByElementIds' => Expected::once(
                function (string $elementType, array $elementIds): array {
                    $this->assertSame(ElementTypes::TYPE_ASSET, $elementType);
                    $this->assertSame([1, 2], $elementIds);

                    return [['cid' => 2, 'workflow' => 'review', 'place' => 'open']];
                }
            ),
        ]);
        $service = new WorkflowStateService(
            $this->createManager(['review' => ['open' => ['visibleInHeader' => true]]]),
            $repository,
            new WorkflowStatusHydrator()
        );

        $service->applyVisibleStates([$first, new stdClass(), $second], ElementTypes::TYPE_ASSET);

        $this->assertSame([], $first->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($second->getWorkflowStates()));
    }

    public function testSkipsTheLookupWithoutWorkflowStateAwareElements(): void
    {
        $repository = $this->makeEmpty(WorkflowElementsRepositoryInterface::class, [
            'fetchPlacesByElementIds' => Expected::never(),
        ]);
        $service = new WorkflowStateService($this->createManager([]), $repository, new WorkflowStatusHydrator());

        $service->applyVisibleStates([new stdClass()], ElementTypes::TYPE_DOCUMENT);
    }

    public function testSkipsElementsTheUserMayOnlyList(): void
    {
        $listOnly = $this->createElement(3, 'object', false);
        $viewable = $this->createElement(4);
        $repository = $this->makeEmpty(WorkflowElementsRepositoryInterface::class, [
            'fetchPlacesByElementIds' => Expected::once(
                function (string $elementType, array $elementIds): array {
                    $this->assertSame([4], $elementIds);

                    return [
                        ['cid' => 3, 'workflow' => 'review', 'place' => 'open'],
                        ['cid' => 4, 'workflow' => 'review', 'place' => 'open'],
                    ];
                }
            ),
        ]);
        $service = new WorkflowStateService(
            $this->createManager(['review' => ['open' => ['visibleInHeader' => true]]]),
            $repository,
            new WorkflowStatusHydrator()
        );

        $service->applyVisibleStates([$listOnly, $viewable], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertNull($listOnly->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($viewable->getWorkflowStates()));
    }

    public function testSkipsAssetFoldersLikeTheEditor(): void
    {
        $folder = $this->createElement(3, 'folder');
        $image = $this->createElement(4, 'image');
        $repository = $this->makeEmpty(WorkflowElementsRepositoryInterface::class, [
            'fetchPlacesByElementIds' => Expected::once(
                function (string $elementType, array $elementIds): array {
                    $this->assertSame([4], $elementIds);

                    return [
                        ['cid' => 3, 'workflow' => 'review', 'place' => 'open'],
                        ['cid' => 4, 'workflow' => 'review', 'place' => 'open'],
                    ];
                }
            ),
        ]);
        $service = new WorkflowStateService(
            $this->createManager(['review' => ['open' => ['visibleInHeader' => true]]]),
            $repository,
            new WorkflowStatusHydrator()
        );

        $service->applyVisibleStates([$folder, $image], ElementTypes::TYPE_ASSET);

        $this->assertNull($folder->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($image->getWorkflowStates()));
    }

    public function testKeepsDataObjectFolders(): void
    {
        $folder = $this->createElement(8, 'folder');
        $service = $this->createService(
            [['cid' => 8, 'workflow' => 'review', 'place' => 'open']],
            ['review' => ['open' => ['visibleInHeader' => true]]]
        );

        $service->applyVisibleStates([$folder], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['open'], $this->getTitles($folder->getWorkflowStates()));
    }

    public function testIgnoresWorkflowsThatAreNoLongerConfigured(): void
    {
        $element = $this->createElement(5);
        $service = $this->createService([['cid' => 5, 'workflow' => 'removed', 'place' => 'gone']], []);

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame([], $element->getWorkflowStates());
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $placeConfigsByWorkflow
     */
    private function createService(array $rows, array $placeConfigsByWorkflow): WorkflowStateService
    {
        return new WorkflowStateService(
            $this->createManager($placeConfigsByWorkflow),
            $this->makeEmpty(WorkflowElementsRepositoryInterface::class, ['fetchPlacesByElementIds' => $rows]),
            new WorkflowStatusHydrator()
        );
    }

    /**
     * Workflows are registered in priority order, which getAllWorkflows() keeps.
     *
     * @param array<string, array<string, array<string, mixed>>> $placeConfigsByWorkflow
     */
    private function createManager(array $placeConfigsByWorkflow): Manager
    {
        $expressionService = $this->makeEmpty(ExpressionService::class);
        $configs = [];
        foreach ($placeConfigsByWorkflow as $workflowName => $placeConfigs) {
            foreach ($placeConfigs as $place => $config) {
                $configs[$workflowName][$place] = new PlaceConfig($place, $config, $expressionService, $workflowName);
            }
        }

        return $this->make(Manager::class, [
            'getAllWorkflows' => array_keys($placeConfigsByWorkflow),
            'getPlaceConfigsByWorkflowName' => static fn (string $workflowName): array => $configs[$workflowName] ?? [],
        ]);
    }

    private function createElement(int $id, string $type = 'object', bool $canView = true): WorkflowStatesAwareInterface
    {
        return new class($id, $type, new Permissions(view: $canView)) implements WorkflowStatesAwareInterface {
            private ?array $workflowStates = null;

            public function __construct(
                private readonly int $id,
                private readonly string $type,
                private readonly Permissions $permissions
            ) {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getType(): string
            {
                return $this->type;
            }

            public function getPermissions(): Permissions
            {
                return $this->permissions;
            }

            public function getWorkflowStates(): ?array
            {
                return $this->workflowStates;
            }

            public function setWorkflowStates(array $workflowStates): void
            {
                $this->workflowStates = $workflowStates;
            }
        };
    }

    /**
     * @param WorkflowStatus[] $states
     *
     * @return string[]
     */
    private function getTitles(array $states): array
    {
        return array_map(static fn (WorkflowStatus $state): string => $state->getTitle(), $states);
    }
}
