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

        return new RelationDataService($this->makeEmpty(ElementDataServiceInterface::class), $patchService);
    }
}
