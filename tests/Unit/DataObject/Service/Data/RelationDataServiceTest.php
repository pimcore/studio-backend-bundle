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

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Repository\RelatedElementRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;

/**
 * @internal
 */
final class RelationDataServiceTest extends Unit
{
    public function testAdvancedObjectRelationsAreResolvedInOneQueryWithoutLoadingElements(): void
    {
        $repository = $this->makeEmpty(RelatedElementRepositoryInterface::class, [
            'getRelatedElementsData' => Expected::once(function (string $type, array $ids): array {
                $this->assertSame('object', $type);
                $this->assertSame([10, 11, 12], $ids);

                return [
                    11 => new RelatedElementData(11, 'object', 'Product', '/products/b', false),
                    10 => new RelatedElementData(10, 'object', 'Product', '/products/a', true),
                ];
            }),
        ]);

        // The relation targets are only known by id here: getObject()/getElement() would load the
        // object from the database, which a unit test without a booted kernel cannot do.
        $relations = [
            $this->objectMetadata(10, ['theme' => 'A']),
            $this->objectMetadata(11, ['theme' => 'B']),
            $this->objectMetadata(12, ['theme' => 'deleted target']),
        ];

        $result = $this->createService($repository)->getAdvancedRelationElementsData($relations);

        $this->assertCount(2, $result);
        $this->assertSame('/products/a', $result[0]['element']->getFullPath());
        $this->assertSame(['theme' => 'A'], $result[0]['data']);
        $this->assertSame('items', $result[0]['fieldName']);
        $this->assertSame(['theme'], $result[0]['columns']);
        $this->assertSame('/products/b', $result[1]['element']->getFullPath());
        $this->assertFalse($result[1]['element']->getIsPublished());
    }

    public function testAdvancedRelationsOfMixedElementTypesUseOneQueryPerType(): void
    {
        $calls = [];
        $repository = $this->makeEmpty(RelatedElementRepositoryInterface::class, [
            'getRelatedElementsData' => Expected::exactly(2, function (string $type, array $ids) use (&$calls): array {
                $calls[$type] = $ids;

                return match ($type) {
                    'asset' => [5 => new RelatedElementData(5, 'asset', 'image', '/images/a.jpg')],
                    'object' => [7 => new RelatedElementData(7, 'object', 'Product', '/products/c', true)],
                };
            }),
        ]);

        $relations = [
            $this->elementMetadata('asset', 5),
            $this->elementMetadata('object', 7),
            $this->elementMetadata('asset', 5),
        ];

        $result = $this->createService($repository)->getAdvancedRelationElementsData($relations);

        $this->assertSame(['asset' => [5], 'object' => [7]], $calls);
        $this->assertSame(
            ['/images/a.jpg', '/products/c', '/images/a.jpg'],
            array_map(static fn (array $row) => $row['element']->getFullPath(), $result)
        );
    }

    public function testNoQueryForEmptyRelations(): void
    {
        $repository = $this->makeEmpty(RelatedElementRepositoryInterface::class, [
            'getRelatedElementsData' => Expected::never(),
        ]);

        $this->assertSame([], $this->createService($repository)->getAdvancedRelationElementsData([]));
    }

    private function createService(RelatedElementRepositoryInterface $repository): RelationDataService
    {
        return new RelationDataService(
            $this->makeEmpty(ElementDataServiceInterface::class),
            $this->makeEmpty(PatchServiceInterface::class),
            $repository
        );
    }

    private function objectMetadata(int $objectId, array $data): ObjectMetadata
    {
        $metadata = new ObjectMetadata('items', ['theme']);
        $metadata->setObjectId($objectId);
        $metadata->setData($data);

        return $metadata;
    }

    private function elementMetadata(string $type, int $id): ElementMetadata
    {
        $metadata = new ElementMetadata('assets', []);
        $metadata->setElementTypeAndId($type, $id);

        return $metadata;
    }
}
