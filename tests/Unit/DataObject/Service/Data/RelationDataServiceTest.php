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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Service\Data;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\DataNormalizerInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\DataAdapterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementIndexServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSaveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\AdapterLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\AdvancedManyToManyRelation;
use Pimcore\Model\DataObject\Concrete;
use function array_column;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use function array_map;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Data\Model\FieldContextData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementSummaryDataServiceInterface;

/**
 * @internal
 */
final class RelationDataServiceTest extends Unit
{
    /**
     * Regression test: "add" on an advanced relation must keep the existing entries and their metadata.
     *
     * @throws Exception
     */
    public function testAdvancedAddKeepsExistingEntriesWithMetadata(): void
    {
        $result = $this->getAdvancedSetterData('add', [$this->newAdvancedEntry(3)], $this->frontAndRear());

        $this->assertSame([1, 2, 3], $this->extractElementIds($result));
        $this->assertSame(['position' => 'front'], $result[0]['data']);
        $this->assertSame(['position' => 'rear'], $result[1]['data']);
        $this->assertSame(['position' => 'roof'], $result[2]['data']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedAddKeepsDataOfAlreadyRelatedElement(): void
    {
        $result = $this->getAdvancedSetterData('add', [$this->newAdvancedEntry(2)], $this->frontAndRear());

        $this->assertSame([1, 2], $this->extractElementIds($result));
        $this->assertSame(['position' => 'front'], $result[0]['data']);
        $this->assertSame(['position' => 'rear'], $result[1]['data']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedAddKeepsRepeatedAssignments(): void
    {
        $result = $this->getAdvancedSetterData(
            'add',
            [$this->newAdvancedEntry(3)],
            $this->repeatedAssignments(),
            true
        );

        $this->assertSame([1, 1, 2, 3], $this->extractElementIds($result));
        $this->assertSame(['position' => 'front'], $result[0]['data']);
        $this->assertSame(['position' => 'back'], $result[1]['data']);
        $this->assertSame(['position' => 'rear'], $result[2]['data']);
        $this->assertSame(['position' => 'roof'], $result[3]['data']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedRemoveRemovesAllAssignmentsOfElement(): void
    {
        $result = $this->getAdvancedSetterData(
            'remove',
            [$this->newAdvancedEntry(1)],
            $this->repeatedAssignments(),
            true
        );

        $this->assertSame([2], $this->extractElementIds($result));
    }

    /**
     * @throws Exception
     */
    public function testAdvancedAddAppendsRepeatedAssignmentsWhenFieldAllowsThem(): void
    {
        $result = $this->getAdvancedSetterData('add', $this->roofAndBackOfElement3(), $this->frontAndRear(), true);

        $this->assertSame([1, 2, 3, 3], $this->extractElementIds($result));
        $this->assertSame(['position' => 'roof'], $result[2]['data']);
        $this->assertSame(['position' => 'back'], $result[3]['data']);
    }

    /**
     * Saving the same element twice would fail AbstractRelations::performMultipleAssignmentCheck().
     *
     * @throws Exception
     */
    public function testAdvancedAddAppendsElementOnceWhenFieldDisallowsRepeatedAssignments(): void
    {
        $result = $this->getAdvancedSetterData('add', $this->roofAndBackOfElement3(), $this->frontAndRear());

        $this->assertSame([1, 2, 3], $this->extractElementIds($result));
        $this->assertSame(['position' => 'roof'], $result[2]['data']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedRemoveKeepsRepeatedAssignmentsOfOtherElement(): void
    {
        $result = $this->getAdvancedSetterData(
            'remove',
            [$this->newAdvancedEntry(2)],
            $this->repeatedAssignments(),
            true
        );

        $this->assertSame([1, 1], $this->extractElementIds($result));
        $this->assertSame(['position' => 'front'], $result[0]['data']);
        $this->assertSame(['position' => 'back'], $result[1]['data']);
    }

    /**
     * Repeats left over from before the field disallowed multiple assignments would fail the save.
     *
     * @throws Exception
     */
    public function testAdvancedAddKeepsFirstOfRepeatedAssignmentsWhenFieldDisallowsThem(): void
    {
        $result = $this->getAdvancedSetterData('add', [$this->newAdvancedEntry(3)], $this->repeatedAssignments());

        $this->assertSame([1, 2, 3], $this->extractElementIds($result));
        $this->assertSame(['position' => 'front'], $result[0]['data']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedAddToFieldWithoutValue(): void
    {
        $result = $this->getAdvancedSetterData('add', [$this->newAdvancedEntry(3)], null);

        $this->assertSame([3], $this->extractElementIds($result));
    }

    /**
     * Regression test: "remove" on an advanced relation must only drop the given entries.
     *
     * @throws Exception
     */
    public function testAdvancedRemoveKeepsOtherEntriesWithMetadata(): void
    {
        $result = $this->getAdvancedSetterData('remove', [$this->newAdvancedEntry(1)], $this->frontAndRear());

        $this->assertSame([2], $this->extractElementIds($result));
        $this->assertSame(['position' => 'rear'], $result[0]['data']);
        $this->assertSame('/cars/2', $result[0]['element']['fullPath']);
    }

    /**
     * @throws Exception
     */
    public function testAdvancedRemoveMatchesByElementIdAndType(): void
    {
        $entry = $this->newAdvancedEntry(1);
        $entry['element']['type'] = 'asset';

        $result = $this->getAdvancedSetterData('remove', [$entry], $this->frontAndRear());

        $this->assertSame([1, 2], $this->extractElementIds($result));
    }

    /**
     * @throws Exception
     */
    public function testAdvancedReplaceReturnsOnlyNewEntries(): void
    {
        $result = $this->getAdvancedSetterData('replace', [$this->newAdvancedEntry(3)], $this->frontAndRear());

        $this->assertSame([3], $this->extractElementIds($result));
    }

    /**
     * @throws Exception
     */
    public function testSimpleAddKeepsExistingEntriesAndAppendsRepeatedElementOnce(): void
    {
        $result = $this->createService()->getSetterData(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(Data::class, ['getName' => 'relations']),
            $this->makeEmpty(DataNormalizerInterface::class, [
                'normalize' => [$this->relatedElement(1), $this->relatedElement(2)],
            ]),
            true,
            ['action' => 'add', 'data' => [['id' => 3, 'type' => 'object'], ['id' => 3, 'type' => 'object']]],
            null
        );

        $this->assertSame([1, 2, 3], array_column($result, 'id'));
    }

    /**
     * @throws Exception
     */
    public function testRelatedElementsAreBuiltFromTheSummaries(): void
    {
        $summaries = [$this->makeSummary(1), $this->makeSummary(2)];
        $service = $this->lightweightCreateService($built);

        $data = $service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: false),
            $this->makeField(optimized: true, summaries: $summaries),
            'objects'
        );

        $this->assertSame($summaries, $built);
        $this->assertSame([1, 2], array_map(static fn (RelatedElementData $d) => $d->getId(), $data));
    }

    /**
     * @throws Exception
     */
    public function testNoRelationsYieldAnEmptyListWithoutInheritance(): void
    {
        $service = $this->lightweightCreateService($built);

        $data = $service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: false),
            $this->makeField(optimized: true, summaries: []),
            'objects'
        );

        $this->assertSame([], $data);
    }

    /**
     * @throws Exception
     */
    public function testRegularLoadingIsUsedWhenTheFlagIsNotSet(): void
    {
        $service = $this->lightweightCreateService($built);

        $data = $service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: false),
            $this->makeField(optimized: false, summaries: [$this->makeSummary(1)]),
            'objects'
        );

        $this->assertNull($data);
        $this->assertSame([], $built);
    }

    /**
     * @throws Exception
     */
    public function testRegularLoadingIsUsedForFieldsWithoutLightweightLoading(): void
    {
        $service = $this->lightweightCreateService($built);

        $this->assertNull($service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: false),
            new Input(),
            'title'
        ));
    }

    /**
     * @throws Exception
     */
    public function testRegularLoadingIsUsedWhenTheValueIsAlreadyInMemory(): void
    {
        $service = $this->lightweightCreateService($built);

        $data = $service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: true, allowInherit: false),
            $this->makeField(optimized: true, summaries: [$this->makeSummary(1)]),
            'objects'
        );

        $this->assertNull($data);
        $this->assertSame([], $built);
    }

    /**
     * @throws Exception
     */
    public function testEmptyValueIsInheritedFromTheNearestAncestorWithRelations(): void
    {
        $service = $this->lightweightCreateService($built, inheritedValues: true);
        $grandparent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 1);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5, parent: $grandparent);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [1 => [$this->makeSummary(7)], 5 => [], 10 => []]);

        $data = $service->getLightweightRelationData($object, $field, 'objects');

        $this->assertSame([7], array_map(static fn (RelatedElementData $d) => $d->getId(), $data));
    }

    /**
     * @throws Exception
     */
    public function testEmptyValueStaysEmptyWhileInheritedValuesAreNotInUse(): void
    {
        $service = $this->lightweightCreateService($built, inheritedValues: false);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [5 => [$this->makeSummary(7)], 10 => []]);

        $this->assertSame([], $service->getLightweightRelationData($object, $field, 'objects'));
    }

    /**
     * @throws Exception
     */
    public function testRegularLoadingIsUsedWhenAnAncestorValueIsAlreadyInMemory(): void
    {
        $service = $this->lightweightCreateService($built, inheritedValues: true);
        $parent = $this->makeObject(lazyKeyLoaded: true, allowInherit: true, id: 5);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [5 => [$this->makeSummary(7)], 10 => []]);

        $this->assertNull($service->getLightweightRelationData($object, $field, 'objects'));
    }

    /**
     * @throws Exception
     */
    public function testOwnRelationsAreNotInherited(): void
    {
        $service = $this->lightweightCreateService($built);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [5 => [$this->makeSummary(7)], 10 => [$this->makeSummary(1)]]);

        $data = $service->getLightweightInheritanceData($object, $field, 'objects', new FieldContextData(resolveInheritedValue: true));

        $this->assertSame(10, $data->getObjectId());
        $this->assertFalse($data->isInherited());
        $this->assertTrue($data->isInheritable());
        $this->assertSame([7], array_map(static fn (RelatedElementData $d) => $d->getId(), $data->getInheritedValue()));
    }

    /**
     * @throws Exception
     */
    public function testInheritedRelationsNameTheirOrigin(): void
    {
        $service = $this->lightweightCreateService($built);
        $grandparent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 1);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5, parent: $grandparent);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [1 => [$this->makeSummary(7)], 5 => [], 10 => []]);

        $data = $service->getLightweightInheritanceData($object, $field, 'objects', new FieldContextData(resolveInheritedValue: true));

        $this->assertSame(1, $data->getObjectId());
        $this->assertTrue($data->isInherited());
        $this->assertSame([7], array_map(static fn (RelatedElementData $d) => $d->getId(), $data->getInheritedValue()));
    }

    /**
     * @throws Exception
     */
    public function testWithoutAnyRelationsTheTerminalAncestorIsTheOrigin(): void
    {
        $service = $this->lightweightCreateService($built);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [5 => [], 10 => []]);

        $data = $service->getLightweightInheritanceData($object, $field, 'objects', new FieldContextData(resolveInheritedValue: true));

        $this->assertSame(5, $data->getObjectId());
        $this->assertTrue($data->isInherited());
        $this->assertNull($data->getInheritedValue());
    }

    /**
     * @throws Exception
     */
    public function testInheritedValueIsNotResolvedUnlessRequested(): void
    {
        $service = $this->lightweightCreateService($built);
        $parent = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 5);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true, id: 10, parent: $parent);
        $field = $this->makeFieldPerObject(optimized: true, summaries: [5 => [$this->makeSummary(7)], 10 => []]);

        $data = $service->getLightweightInheritanceData($object, $field, 'objects');

        $this->assertSame(5, $data->getObjectId());
        $this->assertNull($data->getInheritedValue());
        $this->assertSame([], $built);
    }

    /**
     * @throws Exception
     */
    public function testRegularInheritanceResolutionIsUsedInsideContainers(): void
    {
        $service = $this->lightweightCreateService($built);
        $object = $this->makeObject(lazyKeyLoaded: false, allowInherit: true);
        $field = $this->makeField(optimized: true, summaries: [$this->makeSummary(1)]);

        $this->assertNull($service->getLightweightInheritanceData($object, $field, 'objects', new FieldContextData(language: 'en')));
        $this->assertNull($service->getLightweightInheritanceData($object, $field, 'objects', new FieldContextData(contextObject: $object)));
    }

    /**
     * @throws Exception
     */
    public function testRegularLoadingIsUsedWhenTheElementDataServiceCannotDescribeSummaries(): void
    {
        $elementDataService = $this->makeEmpty(ElementDataServiceInterface::class);
        $service = new RelationDataService(
            $elementDataService,
            $this->makeEmpty(PatchServiceInterface::class),
            $this->makeEmpty(ConcreteObjectResolverInterface::class)
        );

        $this->assertNull($service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: false),
            $this->makeField(optimized: true, summaries: [$this->makeSummary(1)]),
            'objects'
        ));
    }

    /**
     * @throws Exception
     */
    public function testOwnRelationsAreUsedWithInheritance(): void
    {
        $service = $this->lightweightCreateService($built);

        $data = $service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: true),
            $this->makeField(optimized: true, summaries: [$this->makeSummary(1)]),
            'objects'
        );

        $this->assertCount(1, $data);
    }

    /**
     * @param array<int, array<string, mixed>>|null $built
     *
     * @throws Exception
     */
    private function lightweightCreateService(?array &$built = null, bool $inheritedValues = false): RelationDataService
    {
        $built = [];
        $elementDataService = $this->makeEmpty(ElementSummaryDataServiceInterface::class, [
            'getRelatedElementDataFromSummary' => function (array $summary) use (&$built): RelatedElementData {
                $built[] = $summary;

                return new RelatedElementData($summary['id'], 'object', 'Product', $summary['fullpath']);
            },
        ]);
        $concreteObjectResolver = $this->makeEmpty(ConcreteObjectResolverInterface::class, [
            'doGetInheritedValues' => $inheritedValues,
        ]);

        return new RelationDataService(
            $elementDataService,
            $this->makeEmpty(PatchServiceInterface::class),
            $concreteObjectResolver
        );
    }

    /**
     * @param array<int, array<string, mixed>> $summaries
     *
     * @throws Exception
     */
    private function makeField(bool $optimized, array $summaries): ManyToManyObjectRelation
    {
        return $this->makeEmpty(ManyToManyObjectRelation::class, [
            'isOptimizedAdminLoading' => $optimized,
            'supportsInheritance' => true,
            'loadRelationSummaries' => $summaries,
        ]);
    }

    /**
     * @param array<int, array<int, array<string, mixed>>> $summaries summaries keyed by the id of the object they belong to
     *
     * @throws Exception
     */
    private function makeFieldPerObject(bool $optimized, array $summaries): ManyToManyObjectRelation
    {
        return $this->makeEmpty(ManyToManyObjectRelation::class, [
            'isOptimizedAdminLoading' => $optimized,
            'supportsInheritance' => true,
            'loadRelationSummaries' => static fn (Concrete $object): array => $summaries[$object->getId()] ?? [],
        ]);
    }

    /**
     * @throws Exception
     */
    private function makeObject(
        bool $lazyKeyLoaded,
        bool $allowInherit,
        int $id = 10,
        ?Concrete $parent = null
    ): Concrete {
        $class = new ClassDefinition();
        $class->setAllowInherit($allowInherit);

        return $this->makeEmpty(Concrete::class, [
            'getId' => $id,
            'isLazyKeyLoaded' => $lazyKeyLoaded,
            'getClass' => $class,
            'getNextParentForInheritance' => $parent,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeSummary(int $id): array
    {
        return [
            'id' => $id,
            'parentId' => 1,
            'type' => 'object',
            'subtype' => 'object',
            'className' => 'Product',
            'fullpath' => '/products/p' . $id,
            'filename' => 'p' . $id,
            'published' => true,
        ];
    }

    /**
     * @throws Exception
     */
    private function getAdvancedSetterData(
        string $action,
        array $newEntries,
        ?array $existing,
        bool $allowMultipleAssignments = false
    ): array {
        return $this->createService()->getSetterData(
            $this->makeEmpty(Concrete::class),
            $this->makeEmpty(AdvancedManyToManyRelation::class, [
                'getName' => 'relations',
                'getAllowMultipleAssignments' => $allowMultipleAssignments,
            ]),
            $this->makeEmpty(DataNormalizerInterface::class, ['normalize' => $existing]),
            true,
            ['action' => $action, 'data' => $newEntries],
            null,
            true
        );
    }

    /**
     * Shape produced by RelationDataService::getAdvancedRelationElementData() via the adapter's normalize().
     */
    private function existingAdvancedEntry(int $id, array $data): array
    {
        return [
            'element' => $this->relatedElement($id),
            'fieldName' => 'relations',
            'columns' => ['position'],
            'data' => $data,
        ];
    }

    private function frontAndRear(): array
    {
        return [
            $this->existingAdvancedEntry(1, ['position' => 'front']),
            $this->existingAdvancedEntry(2, ['position' => 'rear']),
        ];
    }

    private function roofAndBackOfElement3(): array
    {
        $back = $this->newAdvancedEntry(3);
        $back['data'] = ['position' => 'back'];

        return [$this->newAdvancedEntry(3), $back];
    }

    private function repeatedAssignments(): array
    {
        return [
            $this->existingAdvancedEntry(1, ['position' => 'front']),
            $this->existingAdvancedEntry(1, ['position' => 'back']),
            $this->existingAdvancedEntry(2, ['position' => 'rear']),
        ];
    }

    /**
     * Patch entry of an advanced relation, reduced to the keys that are read (the Studio UI sends more).
     */
    private function newAdvancedEntry(int $id): array
    {
        return [
            'element' => ['id' => $id, 'type' => 'object', 'fullPath' => '/cars/' . $id],
            'data' => ['position' => 'roof'],
        ];
    }

    private function relatedElement(int $id): RelatedElementData
    {
        return new RelatedElementData($id, 'object', 'Car', '/cars/' . $id, true);
    }

    private function extractElementIds(array $result): array
    {
        return array_column(array_column($result, 'element'), 'id');
    }

    /**
     * @throws Exception
     */
    private function createService(): RelationDataService
    {
        $patchService = new PatchService(
            adapterLoader: $this->makeEmpty(AdapterLoaderInterface::class),
            dataAdapterService: $this->makeEmpty(DataAdapterServiceInterface::class),
            elementService: $this->makeEmpty(ElementServiceInterface::class),
            jobExecutionAgent: $this->makeEmpty(JobExecutionAgentInterface::class),
            indexService: $this->makeEmpty(ElementIndexServiceInterface::class),
            elementSaveService: $this->makeEmpty(ElementSaveServiceInterface::class),
            securityService: $this->makeEmpty(SecurityServiceInterface::class),
            coauthorService: $this->makeEmpty(CoauthorServiceInterface::class),
        );

        return new RelationDataService($this->makeEmpty(ElementDataServiceInterface::class), $patchService, $this->makeEmpty(ConcreteObjectResolverInterface::class));
    }
}
