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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Configuration;

use Codeception\Test\Unit;
use Exception;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\SubGroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Repository\ListingDatabaseStubTrait;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig\Listing;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;

/**
 * @internal
 */
final class SubGroupServiceTest extends Unit
{
    use ListingDatabaseStubTrait;

    private const int STORE_ID = 1;

    private const int GROUP_ID = 7;

    private const string IDENTIFIER = '01JBQ8X9Z0ABCDEFGHJKMNPQRS';

    private const string SUB_GROUP_NAME = 'Dimensions';

    private const string RENAMED = 'Measurements';

    /**
     * @throws Exception
     */
    public function testListSubGroupsReturnsOnlyTheStoresEntries(): void
    {
        $this->stubDatabaseReturning(
            [
                $this->createRow(id: 21, name: self::SUB_GROUP_NAME),
                $this->createRow(id: 22, name: 'Logistics'),
            ],
            totalCount: 2
        );

        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('getListing')
            ->with($this->anything(), self::STORE_ID)
            ->willReturn($this->createListing());

        $collection = $this
            ->createService($repository)
            ->listSubGroups(new CollectionFilterParameter(), self::STORE_ID);

        $this->assertSame(2, $collection->getTotalItems());
        $this->assertSame(
            [self::SUB_GROUP_NAME, 'Logistics'],
            array_map(static fn (SubGroupDetail $detail): string => $detail->getName(), $collection->getItems())
        );
        $this->assertSame(
            [self::STORE_ID, self::STORE_ID],
            array_map(static fn (SubGroupDetail $detail): int => $detail->getStoreId(), $collection->getItems())
        );
    }

    /**
     * @throws Exception
     */
    public function testCreateSubGroupReturnsTheHydratedDetail(): void
    {
        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method('create')
            ->with(self::SUB_GROUP_NAME, self::STORE_ID)
            ->willReturn($this->createConfig(self::SUB_GROUP_NAME));

        $detail = $this
            ->createService($repository)
            ->createSubGroup(new SubGroupCreate(self::SUB_GROUP_NAME, self::STORE_ID));

        $this->assertSame(self::SUB_GROUP_NAME, $detail->getName());
        $this->assertSame(self::STORE_ID, $detail->getStoreId());
        $this->assertSame(self::IDENTIFIER, $detail->getIdentifier());
    }

    /**
     * Renaming is the safe operation deleting is not: the assignments name the entry by its
     * generated identifier, which a rename cannot change, so nothing is orphaned and the layout
     * reports the new name under the same identifier.
     *
     * @throws Exception
     */
    public function testRenamingASubGroupKeepsAssignmentsIntact(): void
    {
        $renamed = $this->createConfig(self::RENAMED);

        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method('update')
            ->willReturn($renamed);
        $repository
            ->method('getByIdentifiers')
            ->with(self::STORE_ID, [self::IDENTIFIER])
            ->willReturn([self::IDENTIFIER => $renamed]);

        $detail = $this
            ->createService($repository)
            ->updateSubGroup(21, new SubGroupUpdate(self::RENAMED, 'Physical dimensions'));

        $this->assertSame(self::RENAMED, $detail->getName());
        $this->assertSame(self::IDENTIFIER, $detail->getIdentifier());

        $relation = $this->createRelation(11, self::IDENTIFIER);
        $groupLayout = $this
            ->createGroupService($repository, [11 => $relation])
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore'));

        // the link the assignment holds is untouched by the rename
        $this->assertSame(self::IDENTIFIER, $relation->getSubGroup());
        $this->assertSame(self::IDENTIFIER, $groupLayout->getKeys()[0]->getSubGroup());

        $subGroups = $groupLayout->getSubGroups();
        $this->assertCount(1, $subGroups);
        $this->assertSame(self::IDENTIFIER, $subGroups[0]->getIdentifier());
        $this->assertSame(self::RENAMED, $subGroups[0]->getName());
    }

    /**
     * @throws Exception
     */
    private function createService(SubGroupRepositoryInterface $repository): SubGroupService
    {
        // FilterParameter is final, so the mapper has to hand back a real one
        $filterMapper = $this->createMock(FilterMapperServiceInterface::class);
        $filterMapper
            ->method('getFilterParameters')
            ->willReturn(new FilterParameter());

        return new SubGroupService(
            $this->createMock(EventDispatcherInterface::class),
            $filterMapper,
            $repository,
            new SubGroupHydrator(),
        );
    }

    /**
     * @param KeyGroupRelation[] $relations
     *
     * @throws Exception
     */
    private function createGroupService(
        SubGroupRepositoryInterface $subGroupRepository,
        array $relations
    ): GroupService {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        $group = new GroupConfig();
        $group->setId(self::GROUP_ID);
        $group->setName('Technical');
        $group->setStoreId(self::STORE_ID);

        $groupConfigRepository = $this->createMock(GroupConfigRepositoryInterface::class);
        $groupConfigRepository
            ->method('getById')
            ->with(self::GROUP_ID)
            ->willReturn($group);

        return new GroupService(
            $this->createMock(ConcreteObjectResolverInterface::class),
            $groupConfigRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(GroupHydratorInterface::class),
            $keyRelationResolver,
            $keyGroupLayoutService,
            new GroupLayoutHydrator(),
            $this->createMock(ClassDefinitionResolverInterface::class),
            $subGroupRepository,
        );
    }

    private function createListing(): Listing
    {
        $listing = new Listing();
        $listing->addConditionParam('`storeId` = :storeId', ['storeId' => self::STORE_ID]);

        return $listing;
    }

    private function createConfig(string $name): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(21);
        $config->setName($name);
        $config->setStoreId(self::STORE_ID);
        $config->setDescription('Physical dimensions');
        $config->setIdentifier(self::IDENTIFIER);

        return $config;
    }

    private function createRelation(int $keyId, ?string $subGroup): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName('key' . $keyId);
        $relation->setDescription('');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');
        $relation->setSubGroup($subGroup);

        return $relation;
    }

    /**
     * @return array<string, mixed>
     */
    private function createRow(int $id, string $name): array
    {
        return [
            'id' => $id,
            'storeId' => self::STORE_ID,
            'name' => $name,
            'description' => 'A sub group',
            'identifier' => self::IDENTIFIER . $id,
            'creationDate' => 1734567890,
            'modificationDate' => 1734567891,
        ];
    }
}
