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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GroupUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    private const string GROUP_NAME = 'My Group';

    /**
     * @throws Exception
     */
    public function testCreateGroupClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->createGroup(new GroupCreate(self::GROUP_NAME, 1));
    }

    /**
     * @throws Exception
     */
    public function testUpdateGroupClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->updateGroup(1, new GroupUpdate(self::GROUP_NAME, 'Description'));
    }

    /**
     * @throws Exception
     */
    public function testDeleteGroupClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->deleteGroup(1);
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
    private function createService(KeyRelationResolverInterface $resolver): GroupService
    {
        $repository = $this->createMock(GroupRepositoryInterface::class);
        $repository
            ->method('create')
            ->willReturn(new GroupConfig());
        $repository
            ->method('update')
            ->willReturn(new GroupConfig());

        $hydrator = $this->createMock(GroupHydratorInterface::class);
        $hydrator
            ->method('hydrateGroupDetail')
            ->willReturn(new GroupDetail(1, self::GROUP_NAME, 1));

        return new GroupService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $repository,
            $resolver,
            $hydrator,
        );
    }
}
