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
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use Pimcore\Model\DataObject\Concrete;
use function array_map;

/**
 * @internal
 */
final class RelationDataServiceTest extends Unit
{
    /**
     * @throws Exception
     */
    public function testRelatedElementsAreBuiltFromTheSummaries(): void
    {
        $summaries = [$this->makeSummary(1), $this->makeSummary(2)];
        $service = $this->createService($built);

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
        $service = $this->createService($built);

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
        $service = $this->createService($built);

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
        $service = $this->createService($built);

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
        $service = $this->createService($built);

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
    public function testRegularLoadingIsUsedWhenAnEmptyValueMayBeInherited(): void
    {
        $service = $this->createService($built);

        $this->assertNull($service->getLightweightRelationData(
            $this->makeObject(lazyKeyLoaded: false, allowInherit: true),
            $this->makeField(optimized: true, summaries: []),
            'objects'
        ));
    }

    /**
     * @throws Exception
     */
    public function testOwnRelationsAreUsedWithInheritance(): void
    {
        $service = $this->createService($built);

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
    private function createService(?array &$built = null): RelationDataService
    {
        $built = [];
        $elementDataService = $this->makeEmpty(ElementDataServiceInterface::class, [
            'getRelatedElementDataFromSummary' => function (array $summary) use (&$built): RelatedElementData {
                $built[] = $summary;

                return new RelatedElementData($summary['id'], 'object', 'Product', $summary['fullpath']);
            },
        ]);

        return new RelationDataService($elementDataService, $this->makeEmpty(PatchServiceInterface::class));
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
            'loadRelationSummaries' => $summaries,
        ]);
    }

    /**
     * @throws Exception
     */
    private function makeObject(bool $lazyKeyLoaded, bool $allowInherit): Concrete
    {
        $class = new ClassDefinition();
        $class->setAllowInherit($allowInherit);

        return $this->makeEmpty(Concrete::class, [
            'isLazyKeyLoaded' => $lazyKeyLoaded,
            'getClass' => $class,
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
}
