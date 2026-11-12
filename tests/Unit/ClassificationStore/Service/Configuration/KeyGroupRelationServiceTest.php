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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\Configuration\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\GroupRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\Configuration\KeyRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationCreate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyUnits;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyGroupRelationService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyOptionsResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\SelectOption;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Filter\MappedParameter\FilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\CollectionFilterParameter;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\NullKeyOptionsResolver;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation\Listing;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function count;

/**
 * @internal
 */
final class KeyGroupRelationServiceTest extends Unit
{
    private const int KEY_ID = 11;

    private const int GROUP_ID = 7;

    private const array DEFINITION_OVERRIDE = ['defaultUnit' => 'kg'];

    private const array METADATA = ['source' => 'erp'];

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
        $hydrator = new KeyGroupRelationHydrator(new NullKeyOptionsResolver());

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
     * The detail carries the two per assignment columns of the relation it was hydrated from, which is
     * how the values the upsert preserved reach the response.
     *
     * @throws Exception
     */
    public function testDetailCarriesTheDefinitionOverrideAndMetadataOfTheAssignment(): void
    {
        $relation = $this->createRelation(null);
        $relation->setDefinitionOverride(self::DEFINITION_OVERRIDE);
        $relation->setMetadata(self::METADATA);

        $detail = (new KeyGroupRelationHydrator(new NullKeyOptionsResolver()))->hydrateKeyGroupRelationDetail($relation);

        $this->assertSame(self::DEFINITION_OVERRIDE, $detail->getDefinitionOverride());
        $this->assertSame(self::METADATA, $detail->getMetadata());
    }

    /**
     * The key's own `localized` flag reaches the detail off the key configuration the service
     * already loads for the name and the description - core's relation has no property for it - and
     * a key the configuration no longer carries reports nothing rather than failing the read.
     *
     * @dataProvider localizedProvider
     *
     * @throws Exception
     */
    public function testTheDetailCarriesTheLocalizationFlagOfItsKey(?bool $localized, bool $keyExists): void
    {
        $detail = $this
            ->createService(
                $this->createMock(KeyRelationResolverInterface::class),
                $localized,
                $keyExists
            )
            ->createOrUpdateKeyGroupRelation(new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID));

        $this->assertSame($localized, $detail->getLocalized());
    }

    /**
     * @return array<string, array{0: ?bool, 1: bool}>
     */
    public static function localizedProvider(): array
    {
        return [
            'a key flagged localized reports true' => [true, true],
            'a key flagged not localized reports false' => [false, true],
            'a key stating nothing reports null' => [null, true],
            'a key missing from the configuration reports null' => [null, false],
        ];
    }

    /**
     * Options and units are resolved for the assignment, its override applied, and the key's stored type is
     * published beside them.
     *
     * @throws Exception
     */
    public function testBothReadPathsPublishTheResolvedMembersOfTheAssignment(): void
    {
        $options = [new SelectOption('A', 'a'), new SelectOption('B', 'b')];
        $units = new KeyUnits('kg', ['kg', 'g']);
        $keyOptionsResolver = $this->createMock(KeyOptionsResolverInterface::class);
        $keyOptionsResolver->method('getRelationOptions')->willReturn($options);
        $keyOptionsResolver->method('getRelationOptionsUnavailableReason')->willReturn(null);
        $keyOptionsResolver->method('getRelationUnits')->willReturn($units);

        $relation = $this->createRelation(null);
        $relation->setDefinitionOverride(['options' => [['key' => 'A', 'value' => 'a']], 'validUnits' => ['kg']]);
        $listing = new class([$relation]) extends Listing {
            /**
             * @param KeyGroupRelation[] $rows
             */
            public function __construct(private readonly array $rows)
            {
            }

            public function __call(string $method, array $args): mixed
            {
                return $method === 'load' ? $this->rows : count($this->rows);
            }
        };
        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository->method('getListingByGroupId')->willReturn($listing);

        $service = $this->createService(
            $this->createMock(KeyRelationResolverInterface::class),
            keyOptionsResolver: $keyOptionsResolver,
            repository: $repository
        );

        $listed = $service->listKeyGroupRelations(new CollectionFilterParameter(), self::GROUP_ID)->getItems()[0];
        $upserted = $service->createOrUpdateKeyGroupRelation(new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID));

        foreach ([$listed, $upserted] as $detail) {
            $this->assertSame($options, $detail->getOptions());
            $this->assertNull($detail->getOptionsUnavailableReason());
            $this->assertSame($units, $detail->getUnits());
            $this->assertSame('quantityValue', $detail->getKeyType());
        }

        $this->assertSame(
            ['options' => [['key' => 'A', 'value' => 'a']], 'validUnits' => ['kg']],
            $listed->getDefinitionOverride()
        );
    }

    /**
     * @throws Exception
     */
    public function testARelationWhoseKeyConfigurationIsGonePublishesNoKeyMembers(): void
    {
        $keyOptionsResolver = $this->createMock(KeyOptionsResolverInterface::class);
        $keyOptionsResolver->expects($this->never())->method($this->anything());

        $detail = $this
            ->createService(
                $this->createMock(KeyRelationResolverInterface::class),
                null,
                false,
                $keyOptionsResolver
            )
            ->createOrUpdateKeyGroupRelation(new KeyGroupRelationCreate(self::KEY_ID, self::GROUP_ID));

        $this->assertNull($detail->getKeyType());
        $this->assertNull($detail->getOptions());
        $this->assertNull($detail->getOptionsUnavailableReason());
        $this->assertNull($detail->getUnits());
        $this->assertSame(self::GROUP_ID, $detail->getGroupId());
        $this->assertSame('My Group', $detail->getGroupName());
    }

    /**
     * `KeyGroupRelation::$type` has no default, and the key type is read from the key configuration
     * rather than from the relation, so a relation never given a type does not raise.
     *
     * @throws Exception
     */
    public function testARelationBuiltWithoutATypeDoesNotRaise(): void
    {
        $detail = (new KeyGroupRelationHydrator(new NullKeyOptionsResolver()))
            ->hydrateKeyGroupRelationDetail($this->createRelation(null));

        $this->assertNull($detail->getKeyType());
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
    private function createService(
        KeyRelationResolverInterface $resolver,
        ?bool $keyLocalized = null,
        bool $keyExists = true,
        ?KeyOptionsResolverInterface $keyOptionsResolver = null,
        ?KeyGroupRelationRepositoryInterface $repository = null,
    ): KeyGroupRelationService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);

        $repository ??= $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('createOrUpdate')
            ->willReturn($relation);

        $group = new GroupConfig();
        $group->setName('My Group');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->method('getById')
            ->willReturn($group);

        $filterMapper = $this->createMock(FilterMapperServiceInterface::class);
        $filterMapper->method('getFilterParameters')->willReturn(new FilterParameter());

        $key = new KeyConfig();
        $key->setName('My Key');
        $key->setType('quantityValue');

        $keyLocalizationResolver = new InMemoryKeyLocalizationResolver();

        if ($keyLocalized !== null) {
            $keyLocalizationResolver->setLocalized($key, $keyLocalized);
        }

        $keyRepository = $this->createMock(KeyRepositoryInterface::class);
        $keyRepository
            ->method('getById')
            ->willReturnCallback(static function (int $keyId) use ($key, $keyExists): KeyConfig {
                if (!$keyExists) {
                    throw new NotFoundException('key configuration', $keyId);
                }

                return $key;
            });

        return new KeyGroupRelationService(
            $this->createMock(EventDispatcherInterface::class),
            $filterMapper,
            $repository,
            new KeyGroupRelationHydrator($keyOptionsResolver ?? new NullKeyOptionsResolver()),
            $resolver,
            $groupRepository,
            $keyRepository,
            $keyLocalizationResolver,
        );
    }

    private function createRelation(?string $subGroup): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setSubGroup($subGroup);

        return $relation;
    }
}
