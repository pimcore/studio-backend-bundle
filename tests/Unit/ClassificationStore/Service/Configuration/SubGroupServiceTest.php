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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\SubGroupHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\SubGroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\SubGroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\SubGroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\SubGroupVocabularyResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function array_values;

/**
 * @internal
 */
final class SubGroupServiceTest extends Unit
{
    private const int STORE_ID = 1;

    private const int SUB_GROUP_ID = 7;

    private const int GROUP_ID = 3;

    private const string SUB_GROUP_NAME = 'Dimensions';

    private const string IDENTIFIER = '01JBZ8QF6K3M4N5P6R7S8T9V0W';

    private const string DESCRIPTION = 'Measured properties';

    private const string NEW_NAME = 'Outer dimensions';

    private const string NEW_DESCRIPTION = 'Width, height and depth';

    private const string METHOD_UPDATE = 'update';

    /**
     * @throws Exception
     */
    public function testCreateReturnsTheHydratedDetail(): void
    {
        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method('create')
            ->with(self::SUB_GROUP_NAME, self::STORE_ID, self::DESCRIPTION)
            ->willReturn($this->createConfig(self::SUB_GROUP_NAME));

        $detail = $this
            ->createService($repository)
            ->createSubGroup(new SubGroupCreate(self::SUB_GROUP_NAME, self::STORE_ID, self::DESCRIPTION));

        $this->assertSame(self::SUB_GROUP_ID, $detail->getId());
        $this->assertSame(self::SUB_GROUP_NAME, $detail->getName());
        $this->assertSame(self::STORE_ID, $detail->getStoreId());
        $this->assertSame(self::IDENTIFIER, $detail->getIdentifier());
        $this->assertSame(self::DESCRIPTION, $detail->getDescription());
    }

    /**
     * @throws Exception
     */
    public function testUpdateRenamesAndDescribes(): void
    {
        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method(self::METHOD_UPDATE)
            ->with(self::SUB_GROUP_ID, self::NEW_NAME, self::NEW_DESCRIPTION)
            ->willReturn($this->createConfig(self::NEW_NAME, self::NEW_DESCRIPTION));

        $detail = $this
            ->createService($repository)
            ->updateSubGroup(
                self::SUB_GROUP_ID,
                new SubGroupUpdate(self::NEW_NAME, self::NEW_DESCRIPTION)
            );

        $this->assertSame(self::NEW_NAME, $detail->getName());
        $this->assertSame(self::NEW_DESCRIPTION, $detail->getDescription());
        $this->assertSame(self::IDENTIFIER, $detail->getIdentifier());
    }

    /**
     * @throws Exception
     */
    public function testUpdateOfAnUnknownSubGroupIsNotFound(): void
    {
        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method(self::METHOD_UPDATE)
            ->willThrowException(new NotFoundException('sub group configuration', 404));

        $this->expectException(NotFoundException::class);

        $this->createService($repository)->updateSubGroup(404, new SubGroupUpdate(self::SUB_GROUP_NAME));
    }

    /**
     * The relation column carries the immutable identifier, not the name, so a rename neither reads
     * nor invalidates the key relations of a group: the key list reports the same sub group after it.
     *
     * @throws Exception
     */
    public function testRenameDoesNotTouchAssignments(): void
    {
        $relationRepository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $relationRepository
            ->expects($this->once())
            ->method('getByGroupIds')
            ->with([self::GROUP_ID])
            ->willReturn([$this->createRelation()]);

        $keyRelationResolver = new KeyRelationResolver($relationRepository);

        $repository = $this->createMock(SubGroupRepositoryInterface::class);
        $repository
            ->method(self::METHOD_UPDATE)
            ->willReturn($this->createConfig(self::NEW_NAME));

        $before = $this->getSubGroups($keyRelationResolver);

        $this
            ->createService($repository, $keyRelationResolver)
            ->updateSubGroup(self::SUB_GROUP_ID, new SubGroupUpdate(self::NEW_NAME));

        $this->assertSame([self::IDENTIFIER], $before);
        $this->assertSame($before, $this->getSubGroups($keyRelationResolver));
    }

    /**
     * @return array<int, string|null>
     */
    private function getSubGroups(KeyRelationResolverInterface $keyRelationResolver): array
    {
        return array_map(
            static fn (KeyGroupRelation $relation): ?string => $relation->getSubGroup(),
            array_values($keyRelationResolver->getByGroup(self::GROUP_ID))
        );
    }

    /**
     * @throws Exception
     */
    private function createService(
        SubGroupRepositoryInterface $repository,
        ?KeyRelationResolverInterface $keyRelationResolver = null
    ): SubGroupService {
        return new SubGroupService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $repository,
            $keyRelationResolver ?? $this->createMock(KeyRelationResolverInterface::class),
            $this->createMock(SubGroupVocabularyResolverInterface::class),
            new SubGroupHydrator(),
        );
    }

    private function createConfig(string $name, ?string $description = self::DESCRIPTION): SubGroupConfig
    {
        $config = new SubGroupConfig();
        $config->setId(self::SUB_GROUP_ID);
        $config->setStoreId(self::STORE_ID);
        $config->setName($name);
        $config->setDescription($description);
        $config->setIdentifier(self::IDENTIFIER);

        return $config;
    }

    private function createRelation(): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(11);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setSubGroup(self::IDENTIFIER);

        return $relation;
    }
}
