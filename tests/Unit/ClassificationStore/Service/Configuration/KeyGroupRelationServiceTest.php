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
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyGroupRelationService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;

/**
 * @internal
 */
final class KeyGroupRelationServiceTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private const string RESOLVED_SUB_GROUP = 'Dimensions';

    /**
     * @throws Exception
     */
    public function testCreateOrUpdateKeyGroupRelationClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->createOrUpdateKeyGroupRelation(
            new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID)
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteKeyGroupRelationClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->deleteKeyGroupRelation(self::KEY_ID, self::GROUP_ID);
    }

    /**
     * The detail carries the raw identifier the assignment holds, not a resolved name: the layout
     * endpoints resolve it against the store's vocabulary, the configuration grid does not.
     *
     * @throws Exception
     */
    public function testDetailCarriesTheRawSubGroupOfTheAssignment(): void
    {
        $hydrator = new KeyGroupRelationHydrator();

        $this->assertSame(
            'sg-abc',
            $hydrator->hydrateKeyGroupRelationDetail($this->createRelation('sg-abc'))->getSubGroup()
        );
        $this->assertNull(
            $hydrator->hydrateKeyGroupRelationDetail($this->createRelation(null))->getSubGroup()
        );

        $properties = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(KeyGroupRelationDetail::class))->getProperties()
        );

        $this->assertContains('subGroup', $properties);
        $this->assertNotContains('subGroupName', $properties);
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyGroupRelationClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->updateKeyGroupRelation(
            self::GROUP_ID,
            self::KEY_ID,
            new KeyGroupRelationUpdate(sorter: 5)
        );
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyGroupRelationWritesTheSuppliedFields(): void
    {
        $parameters = new KeyGroupRelationUpdate(sorter: 5, metadata: null);

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->expects($this->once())
            ->method('update')
            ->with(self::KEY_ID, self::GROUP_ID, $parameters)
            ->willReturn($this->createRelation());
        $repository->expects($this->never())->method('createOrUpdate');
        $repository->expects($this->never())->method('delete');

        $detail = $this->createService($this->createPairResolver(), $repository)
            ->updateKeyGroupRelation(self::GROUP_ID, self::KEY_ID, $parameters);

        $this->assertSame(self::KEY_ID, $detail->getKeyId());
    }

    /**
     * @throws Exception
     */
    public function testUpdateKeyGroupRelationThrowsNotFoundForAnUnknownPair(): void
    {
        $resolver = $this->createMock(KeyRelationResolverInterface::class);
        $resolver
            ->method('getByGroupAndKey')
            ->willThrowException(new NotFoundException('KeyGroupRelation', self::KEY_ID));

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository->expects($this->never())->method('update');

        $this->expectException(NotFoundException::class);

        $this->createService($resolver, $repository)->updateKeyGroupRelation(
            self::GROUP_ID,
            self::KEY_ID,
            new KeyGroupRelationUpdate(sorter: 5)
        );
    }

    /**
     * The submitted spelling of a sub group is not what gets published: the classification store
     * resolves it to the identifier of the entry it names while saving, and the response reports what
     * the model carries afterwards.
     *
     * @throws Exception
     */
    public function testUpdateKeyGroupRelationPublishesWhatTheModelReportsAfterTheWrite(): void
    {
        $relation = $this->createRelation();
        $relation->setSubGroup(self::RESOLVED_SUB_GROUP);

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository->method('update')->willReturn($relation);

        $detail = $this->createService(
            $this->createPairResolver(),
            $repository,
            new KeyGroupRelationHydrator()
        )->updateKeyGroupRelation(
            self::GROUP_ID,
            self::KEY_ID,
            new KeyGroupRelationUpdate(subGroup: 'dimensions')
        );

        $this->assertSame(self::RESOLVED_SUB_GROUP, $detail->getSubGroup());
    }

    /**
     * @throws Exception
     */
    private function expectClearedOnce(): KeyRelationResolverInterface
    {
        $resolver = $this->createPairResolver();
        $resolver
            ->expects($this->once())
            ->method('clear');

        return $resolver;
    }

    /**
     * A resolver reporting that the pair carries an assignment.
     *
     * @throws Exception
     */
    private function createPairResolver(): KeyRelationResolverInterface&MockObject
    {
        $resolver = $this->createMock(KeyRelationResolverInterface::class);
        $resolver
            ->method('getByGroupAndKey')
            ->willReturn($this->createRelation());

        return $resolver;
    }

    /**
     * @throws Exception
     */
    private function createService(
        KeyRelationResolverInterface $resolver,
        ?KeyGroupRelationRepositoryInterface $repository = null,
        ?KeyGroupRelationHydratorInterface $hydrator = null,
    ): KeyGroupRelationService {
        if ($repository === null) {
            $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
            $repository
                ->method('createOrUpdate')
                ->willReturn($this->createRelation());
            $repository
                ->method('update')
                ->willReturn($this->createRelation());
        }

        if ($hydrator === null) {
            $hydrator = $this->createMock(KeyGroupRelationHydratorInterface::class);
            $hydrator
                ->method('hydrateKeyGroupRelationDetail')
                ->willReturn(new KeyGroupRelationDetail(self::KEY_ID, self::GROUP_ID, 0, false));
        }

        $group = new GroupConfig();
        $group->setName('My Group');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->method('getById')
            ->willReturn($group);

        $key = new KeyConfig();
        $key->setName('My Key');

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository
            ->method('getById')
            ->willReturn($key);

        return new KeyGroupRelationService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $repository,
            $hydrator,
            $resolver,
            $groupRepository,
            $keyRepository,
        );
    }

    private function createRelation(?string $subGroup = null): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setSubGroup($subGroup);

        return $relation;
    }
}
