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
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Repository\WorkflowElementsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowStateService;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use stdClass;
use function array_map;

/**
 * @internal
 */
final class WorkflowStateServiceTest extends Unit
{
    public function testAppliesVisibleStatesInWorkflowDefinitionOrder(): void
    {
        $element = $this->createElement(12);
        $service = $this->createService(
            [['cid' => 12, 'workflow' => 'enrichment', 'place' => 'done_images,edit_text,hidden']],
            [
                'edit_text' => ['label' => 'Edit Text', 'color' => '#ffa500', 'visibleInHeader' => true],
                'hidden' => ['label' => 'Hidden', 'visibleInHeader' => false],
                'done_images' => [
                    'label' => 'Done Images',
                    'color' => '#008000',
                    'colorInverted' => true,
                    'visibleInHeader' => true,
                ],
                'todo' => ['label' => 'ToDo', 'visibleInHeader' => true],
            ]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $states = $element->getWorkflowStates();
        $this->assertSame(['edit_text', 'done_images'], $this->getTitles($states));
        $this->assertSame('Edit Text', $states[0]->getLabel());
        $this->assertSame('#ffa500', $states[0]->getColor());
        $this->assertFalse($states[0]->getColorInverted());
        $this->assertTrue($states[1]->getColorInverted());
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
            $this->createManager(['open' => ['visibleInHeader' => true]]),
            $repository
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
        $service = new WorkflowStateService($this->createManager([]), $repository);

        $service->applyVisibleStates([new stdClass()], ElementTypes::TYPE_DOCUMENT);
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
            $this->createManager(['open' => ['visibleInHeader' => true]]),
            $repository
        );

        $service->applyVisibleStates([$folder, $image], ElementTypes::TYPE_ASSET);

        $this->assertSame([], $folder->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($image->getWorkflowStates()));
    }

    public function testKeepsDataObjectFolders(): void
    {
        $folder = $this->createElement(8, 'folder');
        $service = $this->createService(
            [['cid' => 8, 'workflow' => 'review', 'place' => 'open']],
            ['open' => ['visibleInHeader' => true]]
        );

        $service->applyVisibleStates([$folder], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['open'], $this->getTitles($folder->getWorkflowStates()));
    }

    public function testIgnoresPlacesThatAreNoLongerConfigured(): void
    {
        $element = $this->createElement(5);
        $service = $this->createService([['cid' => 5, 'workflow' => 'removed', 'place' => 'gone']], []);

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame([], $element->getWorkflowStates());
    }

    private function createService(array $rows, array $placeConfigs): WorkflowStateService
    {
        return new WorkflowStateService(
            $this->createManager($placeConfigs),
            $this->makeEmpty(WorkflowElementsRepositoryInterface::class, ['fetchPlacesByElementIds' => $rows])
        );
    }

    private function createManager(array $placeConfigs): Manager
    {
        $expressionService = $this->makeEmpty(ExpressionService::class);
        $configs = [];
        foreach ($placeConfigs as $place => $config) {
            $configs[$place] = new PlaceConfig($place, $config, $expressionService, 'workflow');
        }

        return $this->make(Manager::class, ['getPlaceConfigsByWorkflowName' => $configs]);
    }

    private function createElement(int $id, string $type = 'object'): WorkflowStatesAwareInterface
    {
        return new class($id, $type) implements WorkflowStatesAwareInterface {
            private array $workflowStates = [];

            public function __construct(private readonly int $id, private readonly string $type)
            {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getType(): string
            {
                return $this->type;
            }

            public function getWorkflowStates(): array
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
