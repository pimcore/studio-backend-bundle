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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassDefinitionResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\GroupLayoutHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\GroupConfigRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\GroupLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyLayout;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Concrete;
use ReflectionClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_keys;
use function array_map;
use function sprintf;

/**
 * @internal
 */
final class GroupServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int GROUP_ID = 3;

    private const int OBJECT_ID = 5;

    private const string FIELD_NAME = 'classificationStore';

    private const string GROUP_NAME = 'Technical';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    /**
     * The largest group ETIM ships, which is the size the per-key label query used to cost 121 queries
     * on.
     */
    private const int ETIM_MAX_KEYS = 121;

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionExposesTheTitleOfEveryKey(): void
    {
        $titles = [11 => 'Weight', 12 => null, 13 => ''];

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn([
                11 => $this->createRelation(11, 'weight'),
                12 => $this->createRelation(12, 'height'),
                13 => $this->createRelation(13, 'depth'),
            ]);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());
        $keyGroupLayoutService
            ->method('getKeyTitle')
            ->willReturnCallback(static fn (int $keyId, string $language): ?string => $titles[$keyId]);

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
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
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID));

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
        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn([
                13 => $this->createRelation(13, 'depth'),
                11 => $this->createRelation(11, 'weight'),
                12 => $this->createRelation(12, 'height'),
            ]);

        $keyGroupLayoutService = $this->createMock(KeyGroupLayoutServiceInterface::class);
        $keyGroupLayoutService
            ->method('getLayoutDefinition')
            ->willReturn(new Input());

        $keys = $this
            ->createService($keyRelationResolver, $keyGroupLayoutService)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([13, 11, 12], array_map(static fn (KeyLayout $key): int => $key->getId(), $keys));
    }

    /**
     * The group's whole label set is read with one statement, so the largest group ETIM ships costs
     * one label query instead of 121.
     *
     * @throws Exception
     */
    public function testGetLayoutDefinitionResolvesEveryKeyLabelAfterOnePreload(): void
    {
        $labels = [];
        for ($keyId = 1; $keyId <= self::ETIM_MAX_KEYS; $keyId++) {
            $labels[$keyId] = [self::LANGUAGE => sprintf('Label %d', $keyId)];
        }

        $keys = $this
            ->createServiceWithRealKeyLabels($labels)
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame([array_keys($labels)], $this->preloadedIds());
        $this->assertCount(self::ETIM_MAX_KEYS, $keys);
        $this->assertSame(
            array_map(static fn (int $keyId): string => sprintf('Label %d', $keyId), array_keys($labels)),
            array_map(static fn (KeyLayout $key): ?string => $key->getTitle(), $keys)
        );
    }

    /**
     * @throws Exception
     */
    public function testGetLayoutDefinitionPublishesTheLabelOfTheRequestingUsersLanguage(): void
    {
        $keys = $this
            ->createServiceWithRealKeyLabels([1 => [self::LANGUAGE => 'Gewicht']])
            ->getLayoutDefinition(self::GROUP_ID, new LayoutParameter(self::FIELD_NAME, self::OBJECT_ID))
            ->getKeys();

        $this->assertSame('Gewicht', $keys[0]->getTitle());
    }

    /**
     * @param array<int, array<string, string>> $labels one entry per key of the group
     *
     * @throws Exception
     */
    private function createServiceWithRealKeyLabels(array $labels): GroupService
    {
        $relations = [];
        $keyConfigs = [];
        foreach (array_keys($labels) as $keyId) {
            $keyName = sprintf('key%d', $keyId);
            $relations[$keyId] = $this->createRelation($keyId, $keyName);
            $keyConfigs[$keyId] = $this->createKeyConfig($keyId, $keyName);
        }

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroup')
            ->with(self::GROUP_ID)
            ->willReturn($relations);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $keyConfigs[$keyId] ?? null);

        $keyGroupLayoutService = new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $this->createLabelResolver($labels),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        return $this->createService($keyRelationResolver, $keyGroupLayoutService);
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
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE),
        );
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
