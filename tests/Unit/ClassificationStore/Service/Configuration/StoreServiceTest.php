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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\StoreHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\StoreRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\StoreService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class StoreServiceTest extends Unit
{
    private const string STORE_NAME = 'My Store';

    /**
     * @throws Exception
     */
    public function testCreateStoreClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->createStore(new StoreCreate(self::STORE_NAME));
    }

    /**
     * @throws Exception
     */
    public function testUpdateStoreClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->updateStore(1, new StoreUpdate(self::STORE_NAME, 'Description'));
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
    private function createService(KeyRelationResolverInterface $resolver): StoreService
    {
        $repository = $this->createMock(StoreRepositoryInterface::class);
        $repository
            ->method('create')
            ->willReturn(new StoreConfig());
        $repository
            ->method('update')
            ->willReturn(new StoreConfig());

        $hydrator = $this->createMock(StoreHydratorInterface::class);
        $hydrator
            ->method('hydrateStoreDetail')
            ->willReturn(new StoreDetail(1, self::STORE_NAME));

        return new StoreService(
            $this->createMock(EventDispatcherInterface::class),
            $resolver,
            $repository,
            $hydrator,
        );
    }
}
