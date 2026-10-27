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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyGroupRelationService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class KeyGroupRelationServiceTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

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
     * @throws Exception
     */
    private function expectClearedOnce(): KeyRelationResolverInterface
    {
        $resolver = $this->createMock(KeyRelationResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('clear');

        return $resolver;
    }

    /**
     * @throws Exception
     */
    private function createService(KeyRelationResolverInterface $resolver): KeyGroupRelationService
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('createOrUpdate')
            ->willReturn($relation);

        $hydrator = $this->createMock(KeyGroupRelationHydratorInterface::class);
        $hydrator
            ->method('hydrateKeyGroupRelationDetail')
            ->willReturn(new KeyGroupRelationDetail(self::KEY_ID, self::GROUP_ID, 0, false));

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
}
