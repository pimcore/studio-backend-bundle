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
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Permissions;
use Pimcore\Bundle\StudioBackendBundle\Response\WorkflowStatesAwareInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Hydrator\WorkflowStatusHydrator;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\WorkflowStatus;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowStateService;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Workflow\EventSubscriber\NotesSubscriber;
use Pimcore\Workflow\ExpressionService;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Place\PlaceConfig;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Workflow\Definition;
use Symfony\Component\Workflow\Marking;
use Symfony\Component\Workflow\MarkingStore\MarkingStoreInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\SupportStrategy\InstanceOfSupportStrategy;
use Symfony\Component\Workflow\Workflow;
use Symfony\Component\Workflow\WorkflowInterface;
use function array_fill_keys;
use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function in_array;

/**
 * @internal
 */
final class WorkflowStateServiceTest extends Unit
{
    private const array ENRICHMENT_PLACES = [
        'edit_text' => ['label' => 'Edit Text', 'color' => '#ffa500', 'visibleInHeader' => true],
        'hidden' => ['label' => 'Hidden', 'visibleInHeader' => false],
        'done_images' => [
            'label' => 'Done Images',
            'color' => '#008000',
            'colorInverted' => true,
            'visibleInHeader' => true,
        ],
        'todo' => ['label' => 'ToDo', 'visibleInHeader' => true],
    ];

    public function testAppliesVisibleStatesInConfigurationOrder(): void
    {
        $element = $this->createElement(12);
        $service = $this->createService(
            ['enrichment' => self::ENRICHMENT_PLACES],
            [12 => [$this->createWorkflow('enrichment', ['done_images', 'edit_text', 'hidden'])]]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $states = $element->getWorkflowStates();
        $this->assertSame(['edit_text', 'done_images'], $this->getTitles($states));
        $this->assertSame('Edit Text', $states[0]->getLabel());
        $this->assertSame('#ffa500', $states[0]->getColor());
        $this->assertFalse($states[0]->getColorInverted());
        $this->assertTrue($states[1]->getColorInverted());
    }

    public function testKeepsTheWorkflowOrderOfTheDetailView(): void
    {
        $element = $this->createElement(7);
        $service = $this->createService(
            [
                'zeta' => ['open' => ['label' => 'Zeta open', 'visibleInHeader' => true]],
                'alpha' => ['open' => ['label' => 'Alpha open', 'visibleInHeader' => true]],
            ],
            [7 => [$this->createWorkflow('zeta', ['open']), $this->createWorkflow('alpha', ['open'])]]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_ASSET);

        $labels = array_map(
            static fn (WorkflowStatus $state): string => $state->getLabel(),
            $element->getWorkflowStates()
        );
        $this->assertSame(['Zeta open', 'Alpha open'], $labels);
    }

    public function testShowsNoStateForAWorkflowThatDoesNotSupportTheElement(): void
    {
        $supported = $this->createElement(1);
        $unsupported = $this->createElement(2);
        $service = $this->createService(
            ['enrichment' => self::ENRICHMENT_PLACES],
            [1 => [$this->createWorkflow('enrichment', ['todo'])], 2 => []]
        );

        $service->applyVisibleStates([$supported, $unsupported], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['todo'], $this->getTitles($supported->getWorkflowStates()));
        $this->assertSame([], $unsupported->getWorkflowStates());
    }

    public function testShowsTheInitialPlacesWithoutStoringThem(): void
    {
        $element = $this->createElement(3);
        $service = $this->createService(
            ['enrichment' => self::ENRICHMENT_PLACES],
            [3 => [$this->createWorkflow('enrichment', [], ['todo'])]]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['todo'], $this->getTitles($element->getWorkflowStates()));
    }

    public function testResolvesSupportPriorityAndPlaceOrderThroughTheWorkflowManager(): void
    {
        $registry = new Registry();
        $manager = new Manager(
            $registry,
            $this->makeEmpty(NotesSubscriber::class),
            $this->makeEmpty(ExpressionService::class),
            $this->makeEmpty(EventDispatcherInterface::class)
        );
        $places = ['open' => ['done', 'edit'], 'review' => ['checked']];
        foreach (['open' => 1, 'review' => 5] as $workflowName => $priority) {
            $markingStore = $this->makeEmpty(MarkingStoreInterface::class, [
                'getMarking' => new Marking(array_fill_keys($places[$workflowName], 1)),
                'setMarking' => Expected::never(),
            ]);
            $registry->addWorkflow(
                new Workflow(new Definition(['edit', 'done', 'checked'], []), $markingStore, null, $workflowName),
                new InstanceOfSupportStrategy(Concrete::class)
            );
            $manager->registerWorkflow($workflowName, ['priority' => $priority]);
            foreach (['edit', 'done', 'checked'] as $place) {
                $manager->addPlaceConfig($workflowName, $place, ['visibleInHeader' => true]);
            }
        }
        $subjects = [
            1 => $this->makeEmpty(Concrete::class, ['getId' => 1]),
            2 => $this->makeEmpty(Folder::class, ['getId' => 2]),
        ];
        $service = new WorkflowStateService(
            $manager,
            $this->makeEmpty(ServiceResolverInterface::class, [
                'getElementById' => static fn (string $type, int $id): AbstractObject => $subjects[$id],
            ]),
            new WorkflowStatusHydrator(),
            $this->makeEmpty(LoggerInterface::class, ['warning' => Expected::never()])
        );
        $object = $this->createElement(1);
        $folder = $this->createElement(2, 'folder');

        $service->applyVisibleStates([$object, $folder], ElementTypes::TYPE_DATA_OBJECT);

        // the higher priority workflow first, each workflow's places in configuration order
        $this->assertSame(['checked', 'edit', 'done'], $this->getTitles($object->getWorkflowStates()));
        $this->assertSame([], $folder->getWorkflowStates());
    }

    public function testLoadsEveryElementByItsCoreType(): void
    {
        $loaded = [];
        $serviceResolver = $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (string $type, int $id) use (&$loaded): ElementInterface {
                $loaded[] = [$type, $id];

                return $this->makeEmpty(AbstractObject::class, ['getId' => $id]);
            },
        ]);
        $service = new WorkflowStateService(
            $this->createManager(['enrichment' => self::ENRICHMENT_PLACES], []),
            $serviceResolver,
            new WorkflowStatusHydrator(),
            $this->makeEmpty(LoggerInterface::class)
        );

        $service->applyVisibleStates(
            [$this->createElement(1), new stdClass(), $this->createElement(2)],
            ElementTypes::TYPE_DATA_OBJECT
        );

        $this->assertSame([['object', 1], ['object', 2]], $loaded);
    }

    public function testLoadsNothingWithoutConfiguredWorkflows(): void
    {
        $element = $this->createElement(1);
        $service = new WorkflowStateService(
            $this->createManager([], []),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => Expected::never()]),
            new WorkflowStatusHydrator(),
            $this->makeEmpty(LoggerInterface::class)
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DOCUMENT);

        $this->assertSame([], $element->getWorkflowStates());
    }

    public function testSkipsElementsTheUserMayOnlyList(): void
    {
        $listOnly = $this->createElement(3, 'object', false);
        $viewable = $this->createElement(4);
        $service = $this->createService(
            ['review' => ['open' => ['visibleInHeader' => true]]],
            [4 => [$this->createWorkflow('review', ['open'])]],
            [4]
        );

        $service->applyVisibleStates([$listOnly, $viewable], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertNull($listOnly->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($viewable->getWorkflowStates()));
    }

    public function testShowsNoStatesWhenTheCurrentPlaceDeniesView(): void
    {
        $denied = $this->createElement(3);
        $allowed = $this->createElement(4);
        $service = $this->createService(
            ['review' => [
                'locked' => ['visibleInHeader' => true, 'permissions' => [['view' => false]]],
                'open' => ['visibleInHeader' => true, 'permissions' => [['publish' => false]]],
            ]],
            [
                3 => [$this->createWorkflow('review', ['locked'])],
                4 => [$this->createWorkflow('review', ['open'])],
            ]
        );

        $service->applyVisibleStates([$denied, $allowed], ElementTypes::TYPE_DATA_OBJECT);

        // the search index allows view for both, only the workflow place denies it
        $this->assertNull($denied->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($allowed->getWorkflowStates()));
    }

    public function testKeepsTheStatesWhenTheDenyingPermissionConditionIsNotMet(): void
    {
        $element = $this->createElement(3);
        $service = $this->createService(
            ['review' => [
                'locked' => [
                    'visibleInHeader' => true,
                    'permissions' => [['condition' => 'is_granted("ROLE_GUEST")', 'view' => false]],
                ],
            ]],
            [3 => [$this->createWorkflow('review', ['locked'])]]
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['locked'], $this->getTitles($element->getWorkflowStates()));
    }

    public function testSkipsAssetFoldersLikeTheEditor(): void
    {
        $folder = $this->createElement(3, 'folder');
        $image = $this->createElement(4, 'image');
        $service = $this->createService(
            ['review' => ['open' => ['visibleInHeader' => true]]],
            [4 => [$this->createWorkflow('review', ['open'])]],
            [4]
        );

        $service->applyVisibleStates([$folder, $image], ElementTypes::TYPE_ASSET);

        $this->assertNull($folder->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($image->getWorkflowStates()));
    }

    public function testKeepsDataObjectFolders(): void
    {
        $folder = $this->createElement(8, 'folder');
        $service = $this->createService(
            ['review' => ['open' => ['visibleInHeader' => true]]],
            [8 => [$this->createWorkflow('review', ['open'])]]
        );

        $service->applyVisibleStates([$folder], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame(['open'], $this->getTitles($folder->getWorkflowStates()));
    }

    public function testShowsNoStateForAnElementThatNoLongerExists(): void
    {
        $element = $this->createElement(5);
        $service = new WorkflowStateService(
            $this->createManager(['review' => ['open' => ['visibleInHeader' => true]]], []),
            $this->makeEmpty(ServiceResolverInterface::class, ['getElementById' => null]),
            new WorkflowStatusHydrator(),
            $this->makeEmpty(LoggerInterface::class)
        );

        $service->applyVisibleStates([$element], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame([], $element->getWorkflowStates());
    }

    public function testAFailingElementShowsNoStateAndKeepsItsSiblings(): void
    {
        $failing = $this->createElement(5);
        $sibling = $this->createElement(6);
        $manager = $this->createManager(
            ['review' => ['open' => ['visibleInHeader' => true]]],
            [6 => [$this->createWorkflow('review', ['open'])]],
            static fn (ElementInterface $subject) => $subject->getId() === 5
                ? throw new RuntimeException('Unable to call method "getKey" of non-object "subject.getCategory()".')
                : null
        );
        $logger = $this->makeEmpty(LoggerInterface::class, ['warning' => Expected::once()]);
        $service = new WorkflowStateService(
            $manager,
            $this->createServiceResolver(),
            new WorkflowStatusHydrator(),
            $logger
        );

        $service->applyVisibleStates([$failing, $sibling], ElementTypes::TYPE_DATA_OBJECT);

        $this->assertSame([], $failing->getWorkflowStates());
        $this->assertSame(['open'], $this->getTitles($sibling->getWorkflowStates()));
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $placeConfigsByWorkflow
     * @param array<int, WorkflowInterface[]> $workflowsByElementId
     * @param int[]|null $loadableIds
     */
    private function createService(
        array $placeConfigsByWorkflow,
        array $workflowsByElementId,
        ?array $loadableIds = null
    ): WorkflowStateService {
        return new WorkflowStateService(
            $this->createManager($placeConfigsByWorkflow, $workflowsByElementId),
            $this->createServiceResolver($loadableIds),
            new WorkflowStatusHydrator(),
            $this->makeEmpty(LoggerInterface::class)
        );
    }

    /**
     * @param int[]|null $loadableIds
     */
    private function createServiceResolver(?array $loadableIds = null): ServiceResolverInterface
    {
        return $this->makeEmpty(ServiceResolverInterface::class, [
            'getElementById' => function (string $type, int $id) use ($loadableIds): ElementInterface {
                if ($loadableIds !== null) {
                    $this->assertContains($id, $loadableIds);
                }

                return $this->makeEmpty(AbstractObject::class, ['getId' => $id]);
            },
        ]);
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $placeConfigsByWorkflow
     * @param array<int, WorkflowInterface[]> $workflowsByElementId
     */
    private function createManager(
        array $placeConfigsByWorkflow,
        array $workflowsByElementId,
        ?callable $beforeSupportCheck = null
    ): Manager {
        $expressionService = $this->makeEmpty(ExpressionService::class);
        $configs = [];
        foreach ($placeConfigsByWorkflow as $workflowName => $placeConfigs) {
            foreach ($placeConfigs as $place => $config) {
                $configs[$workflowName][] = new PlaceConfig($place, $config, $expressionService, $workflowName);
            }
        }

        return $this->make(Manager::class, [
            'getAllWorkflows' => array_keys($placeConfigsByWorkflow),
            'getAllWorkflowsForSubject' => static function (ElementInterface $subject) use (
                $workflowsByElementId,
                $beforeSupportCheck
            ): array {
                if ($beforeSupportCheck !== null) {
                    $beforeSupportCheck($subject);
                }

                return $workflowsByElementId[$subject->getId()] ?? [];
            },
            // as the core does: the marked places in configuration order
            'getOrderedPlaceConfigs' => static fn (WorkflowInterface $workflow, Marking $marking): array
                => array_values(array_filter(
                    $configs[$workflow->getName()] ?? [],
                    static fn (PlaceConfig $config): bool
                        => in_array($config->getPlace(), array_keys($marking->getPlaces()), true)
                )),
        ]);
    }

    /**
     * The workflow's own getMarking() and the store's setMarking() would write, the tree only reads.
     *
     * @param string[] $markedPlaces
     * @param string[] $initialPlaces
     */
    private function createWorkflow(string $name, array $markedPlaces, array $initialPlaces = []): WorkflowInterface
    {
        $markingStore = $this->makeEmpty(MarkingStoreInterface::class, [
            'getMarking' => new Marking(array_fill_keys($markedPlaces, 1)),
            'setMarking' => Expected::never(),
        ]);

        return $this->makeEmpty(WorkflowInterface::class, [
            'getName' => $name,
            'getMarking' => Expected::never(),
            'getMarkingStore' => $markingStore,
            'getDefinition' => new Definition([...$markedPlaces, ...$initialPlaces], [], $initialPlaces),
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
