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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyGroupRelationService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Listing\Service\FilterMapperServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\InMemoryKeyLocalizationResolver;
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

        $detail = (new KeyGroupRelationHydrator())->hydrateKeyGroupRelationDetail($relation);

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
        bool $keyExists = true
    ): KeyGroupRelationService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('createOrUpdate')
            ->willReturn($relation);

        $group = new GroupConfig();
        $group->setName('My Group');

        $groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $groupRepository
            ->method('getById')
            ->willReturn($group);

        $key = new KeyConfig();
        $key->setName('My Key');

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
            $this->createMock(FilterMapperServiceInterface::class),
            $repository,
            new KeyGroupRelationHydrator(),
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
