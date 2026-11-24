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
use Exception;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\Data\RelationDataService;
use Pimcore\Bundle\StudioBackendBundle\Element\Repository\RelatedElementRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\RelatedElementData;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDataServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Patcher\Service\PatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\Concrete;
use Pimcore\Model\DataObject\Data\ElementMetadata;
use Pimcore\Model\DataObject\Data\ObjectMetadata;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\UserInterface;
use function array_map;

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

    /**
     * The query path stays in use for an admin as long as no workflow place can deny the view
     * permission: a disabled workflow and places without permissions do not count.
     *
     * @throws Exception
     */
    public function testWorkflowsThatCannotDenyAPermissionKeepTheQueryPath(): void
    {
        $repository = $this->makeEmpty(RelatedElementRepositoryInterface::class, [
            'getRelatedElementsData' => Expected::once([
                10 => new RelatedElementData(10, 'object', 'Product', '/products/a', true),
            ]),
        ]);
        $service = $this->createService($repository, workflowConfigs: [
            'disabled' => ['enabled' => false, 'places' => ['secret' => ['permissions' => [['view' => false]]]]],
            'plain' => ['enabled' => true, 'places' => ['open' => ['label' => 'Open', 'permissions' => []]]],
        ]);

        $result = $service->getAdvancedRelationElementsData([$this->objectMetadata(10, [])]);

        $this->assertSame('/products/a', $result[0]['element']->getFullPath());
    }

    /**
     * GHSA-pg5f-74vr-w237: the rows of the query carry no permission information. For a user who may
     * not view every element each target is checked, and nothing but id and type is handed out for a
     * target without view access.
     *
     * @throws Exception
     */
    public function testAdvancedRelationTargetsAreCheckedForARestrictedUser(): void
    {
        $this->assertAdvancedRelationTargetsAreChecked(
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => false]),
            ])
        );
    }

    /**
     * @throws Exception
     */
    public function testAdvancedRelationTargetsAreCheckedWithoutAUser(): void
    {
        $this->assertAdvancedRelationTargetsAreChecked(
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => static function (): UserInterface {
                    throw new UserNotFoundException();
                },
            ])
        );
    }

    /**
     * A workflow place can deny the view permission for an admin as well.
     *
     * @throws Exception
     */
    public function testAdvancedRelationTargetsAreCheckedForAnAdminWhenAWorkflowPlaceSetsPermissions(): void
    {
        $this->assertAdvancedRelationTargetsAreChecked(
            $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            ]),
            ['approval' => ['places' => ['confidential' => ['permissions' => [['view' => false]]]]]]
        );
    }

    /**
     * @throws Exception
     */
    public function testLoadedRelationTargetsWithoutViewAccessAreWithheld(): void
    {
        $service = $this->createService(
            $this->makeEmpty(RelatedElementRepositoryInterface::class),
            $this->elementDataServiceWithViewAccess(static fn (int $id): bool => $id !== 2)
        );

        $data = $service->getRelationElementsData([
            $this->makeEmpty(Concrete::class, ['getId' => 1]),
            $this->makeEmpty(Concrete::class, ['getId' => 2]),
        ]);

        $this->assertSame('/objects/1', $data[0]->getFullPath());
        $this->assertTrue($data[0]->getHasViewAccess());
        $this->assertSame(2, $data[1]->getId());
        $this->assertSame('object', $data[1]->getType());
        $this->assertSame('', $data[1]->getFullPath());
        $this->assertSame('', $data[1]->getSubtype());
        $this->assertNull($data[1]->getIsPublished());
        $this->assertFalse($data[1]->getHasViewAccess());
    }

    /**
     * @throws Exception
     */
    public function testAdvancedRelationTargetWithoutViewAccessIsWithheld(): void
    {
        $service = $this->createService(
            $this->makeEmpty(RelatedElementRepositoryInterface::class),
            $this->elementDataServiceWithViewAccess(static fn (int $id): bool => false)
        );

        $row = $service->getAdvancedRelationElementData($this->loadedObjectMetadata(2, ['theme' => 'B']));

        $this->assertSame('', $row['element']->getFullPath());
        $this->assertSame('', $row['element']->getSubtype());
        $this->assertFalse($row['element']->getHasViewAccess());
        $this->assertSame(['theme' => 'B'], $row['data']);
    }

    /**
     * @param array<string, array<string, mixed>> $workflowConfigs
     *
     * @throws Exception
     */
    private function assertAdvancedRelationTargetsAreChecked(
        SecurityServiceInterface $securityService,
        array $workflowConfigs = []
    ): void {
        $service = $this->createService(
            $this->makeEmpty(RelatedElementRepositoryInterface::class, [
                'getRelatedElementsData' => Expected::never(),
            ]),
            $this->elementDataServiceWithViewAccess(static fn (int $id): bool => $id !== 2),
            $securityService,
            $workflowConfigs
        );

        $result = $service->getAdvancedRelationElementsData([
            $this->loadedObjectMetadata(1, ['theme' => 'A']),
            $this->loadedObjectMetadata(2, ['theme' => 'B']),
            // the target was deleted: like a missing row of the query, the relation is left out
            $this->makeEmpty(ObjectMetadata::class, ['getElement' => null]),
        ]);

        $this->assertCount(2, $result);
        $this->assertSame('/objects/1', $result[0]['element']->getFullPath());
        $this->assertTrue($result[0]['element']->getHasViewAccess());
        $this->assertSame(2, $result[1]['element']->getId());
        $this->assertSame('', $result[1]['element']->getFullPath());
        $this->assertSame('', $result[1]['element']->getSubtype());
        $this->assertNull($result[1]['element']->getIsPublished());
        $this->assertFalse($result[1]['element']->getHasViewAccess());
        $this->assertSame(['theme' => 'B'], $result[1]['data']);
        $this->assertSame('items', $result[1]['fieldName']);
    }

    /**
     * The service is used by an admin of a project without workflows unless stated otherwise: the
     * only user the query path is meant for.
     *
     * @param array<string, array<string, mixed>> $workflowConfigs
     *
     * @throws Exception
     */
    private function createService(
        RelatedElementRepositoryInterface $repository,
        ?ElementDataServiceInterface $elementDataService = null,
        ?SecurityServiceInterface $securityService = null,
        array $workflowConfigs = []
    ): RelationDataService {
        return new RelationDataService(
            $elementDataService ?? $this->makeEmpty(ElementDataServiceInterface::class),
            $this->makeEmpty(PatchServiceInterface::class),
            $repository,
            $securityService ?? $this->makeEmpty(SecurityServiceInterface::class, [
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['isAdmin' => true]),
            ]),
            $workflowConfigs
        );
    }

    /**
     * An element data service that decides view access by the element id.
     *
     * @param callable(int): bool $hasViewAccess
     *
     * @throws Exception
     */
    private function elementDataServiceWithViewAccess(callable $hasViewAccess): ElementDataServiceInterface
    {
        return $this->makeEmpty(ElementDataServiceInterface::class, [
            'getRelatedElementData' => static fn (ElementInterface $element) => new RelatedElementData(
                $element->getId(),
                'object',
                'Product',
                '/objects/' . $element->getId(),
                true,
                $hasViewAccess($element->getId())
            ),
        ]);
    }

    private function objectMetadata(int $objectId, array $data): ObjectMetadata
    {
        $metadata = new ObjectMetadata('items', ['theme']);
        $metadata->setObjectId($objectId);
        $metadata->setData($data);

        return $metadata;
    }

    /**
     * A relation whose target is already loaded, so that no database is needed to resolve it.
     *
     * @throws Exception
     */
    private function loadedObjectMetadata(int $objectId, array $data): ObjectMetadata
    {
        return $this->makeEmpty(ObjectMetadata::class, [
            'getElement' => $this->makeEmpty(Concrete::class, ['getId' => $objectId]),
            'getFieldname' => 'items',
            'getColumns' => ['theme'],
            'getData' => $data,
        ]);
    }

    private function elementMetadata(string $type, int $id): ElementMetadata
    {
        $metadata = new ElementMetadata('assets', []);
        $metadata->setElementTypeAndId($type, $id);

        return $metadata;
    }
}
