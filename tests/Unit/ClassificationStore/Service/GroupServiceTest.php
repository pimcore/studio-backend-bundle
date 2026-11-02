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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service;

use Codeception\Test\Unit;
use Exception;
use OpenApi\Attributes\Schema;
use PHPUnit\Framework\MockObject\MockObject;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function range;
use function sprintf;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    private const int GROUP_ID = 3;

    private const int OBJECT_ID = 5;

    private const string GROUP_NAME = 'Technical';

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheTitleOfEveryKey(): void
    {
        $titles = [11 => 'Weight', 12 => null, 13 => ''];

        $keyRelationResolver = $this->createKeyRelationResolver([
            11 => $this->createRelation(11, 'weight'),
            12 => $this->createRelation(12, 'height'),
            13 => $this->createRelation(13, 'depth'),
        ]);

        $keyGroupLayoutService = $this->createKeyGroupLayoutService();
        $keyGroupLayoutService
            ->method('getKeyTitle')
            ->willReturnCallback(static fn (int $keyId): ?string => $titles[$keyId]);

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
            ->getKeys();

        $this->assertCount(3, $keys);
        $this->assertSame('Weight', $keys[0]->getTitle());
        $this->assertNull($keys[1]->getTitle());
        $this->assertSame('', $keys[2]->getTitle());
    }

    /**
     * A group added in the session is served by this endpoint and has to report the same shape as a
     * pre-loaded one, so it carries a title too. classificationstore_groups has no title column, so
     * the title is the group name until package 16 lands.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheGroupTitle(): void
    {
        $groupLayout = $this
            ->createService(
                $this->createMock(KeyRelationResolverInterface::class),
                $this->createMock(KeyGroupLayoutServiceInterface::class)
            )
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID));

        $this->assertSame(self::GROUP_NAME, $groupLayout->getTitle());

        $attributes = (new ReflectionClass(GroupLayout::class))->getAttributes(Schema::class);

        $this->assertSame(
            ['id', 'name', 'description', 'keys'],
            $attributes[0]->getArguments()['required']
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionKeepsRelationOrder(): void
    {
        $keyRelationResolver = $this->createKeyRelationResolver([
            13 => $this->createRelation(13, 'depth'),
            11 => $this->createRelation(11, 'weight'),
            12 => $this->createRelation(12, 'height'),
        ]);

        $keys = $this
            ->createService($keyRelationResolver, $this->createKeyGroupLayoutService())
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([13, 11, 12], array_map(static fn (KeyLayout $key): int => $key->getId(), $keys));
    }

    /**
     * The labels of the whole group are read with one call, before the first key is labelled, so an
     * ETIM-sized group costs no query per key.
     *
     * @throws Exception
     */
    public function testTheLabelsOfTheWholeGroupArePreloadedWithOneCall(): void
    {
        $keyIds = range(1, 121);
        $relations = [];
        foreach ($keyIds as $keyId) {
            $relations[$keyId] = $this->createRelation($keyId, sprintf('key%d', $keyId));
        }

        $keyRelationResolver = $this->createKeyRelationResolver($relations);

        $preloaded = false;
        $labelled = 0;

        $keyGroupLayoutService = $this->createKeyGroupLayoutService();
        $keyGroupLayoutService
            ->expects($this->once())
            ->method('preloadKeyTitles')
            ->with($this->callback(static function (array $ids) use ($keyIds, &$preloaded): bool {
                $preloaded = true;

                return $ids === $keyIds;
            }));
        $keyGroupLayoutService
            ->method('getKeyTitle')
            ->willReturnCallback(function () use (&$preloaded, &$labelled): ?string {
                $this->assertTrue($preloaded, 'a key was labelled before the preload');
                ++$labelled;

                return null;
            });

        $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter('classificationStore', self::OBJECT_ID));

        $this->assertSame(121, $labelled);
    }

    /**
     * @throws Exception
     */
    private function createService(
        KeyRelationResolverInterface $keyRelationResolver,
        KeyGroupLayoutServiceInterface $keyGroupLayoutService
    ): GroupService {
        $concreteObjectResolver = $this->createMock(ConcreteObjectResolverInterface::class);
        $concreteObjectResolver
            ->method('getById')
            ->with(self::OBJECT_ID)
            ->willReturn($this->createMock(Concrete::class));

        $group = new GroupConfig();
        $group->setId(self::GROUP_ID);
        $group->setName(self::GROUP_NAME);
        $group->setDescription('Technical attributes');

        $groupConfigRepository = $this->createMock(GroupConfigRepositoryInterface::class);
        $groupConfigRepository
            ->method('getById')
            ->with(self::GROUP_ID)
            ->willReturn($group);

        return new GroupService(
            $concreteObjectResolver,
            $groupConfigRepository,
            $this->createMock(EventDispatcherInterface::class),
            $this->createMock(GroupHydratorInterface::class),
            $keyRelationResolver,
            $keyGroupLayoutService,
            new GroupLayoutHydrator(),
            $this->createMock(ClassDefinitionResolverInterface::class),
        );
    }

    /**
     * @param array<int, KeyGroupRelation> $relations
     */
    private function createKeyRelationResolver(array $relations): KeyRelationResolverInterface&MockObject
    {
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        return $keyRelationResolver;
    }

    private function createKeyGroupLayoutService(): KeyGroupLayoutServiceInterface&MockObject
    {
        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        return $keyGroupLayoutService;
    }

    private function createRelation(int $keyId, string $name): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName($name);
        $relation->setDescription('');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');

        return $relation;
    }
}
