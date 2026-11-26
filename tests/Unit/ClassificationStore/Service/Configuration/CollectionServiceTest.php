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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\CollectionHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\CollectionRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\CollectionUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\CollectionService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final class CollectionServiceTest extends Unit
{
    private const string COLLECTION_NAME = 'My Collection';

    /**
     * @throws Exception
     */
    public function testCreateCollectionClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->createCollection(new CollectionCreate(self::COLLECTION_NAME, 1));
    }

    /**
     * @throws Exception
     */
    public function testUpdateCollectionClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->updateCollection(
            1,
            new CollectionUpdate(self::COLLECTION_NAME, 'Description')
        );
    }

    /**
     * @throws Exception
     */
    public function testDeleteCollectionClearsTheKeyRelationMemo(): void
    {
        $resolver = $this->expectClearedOnce();

        $this->createService($resolver)->deleteCollection(1);
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
    private function createService(KeyRelationResolverInterface $resolver): CollectionService
    {
        $repository = $this->createMock(CollectionRepositoryInterface::class);
        $repository
            ->method('create')
            ->willReturn(new CollectionConfig());
        $repository
            ->method('update')
            ->willReturn(new CollectionConfig());

        $hydrator = $this->createMock(CollectionHydratorInterface::class);
        $hydrator
            ->method('hydrateCollectionDetail')
            ->willReturn(new CollectionDetail(1, self::COLLECTION_NAME, 1));

        return new CollectionService(
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(FilterMapperServiceInterface::class),
            $resolver,
            $repository,
            $hydrator,
        );
    }
}
