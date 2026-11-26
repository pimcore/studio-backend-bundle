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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\GroupConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator\KeyGroupRelationHydrator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\ListClassificationStoreParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Repository\KeyGroupRelationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\GroupServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupRelationService;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * The store-wide key listing the object editor's add-keys modal and the grid column picker read.
 * A key's `localized` flag is a column of the key, and core's relation listing keeps only the joined
 * columns it has a property for - it has none for this one - so the flag is fetched for the page.
 *
 * The title is the same story, and comes out of the same read. Once for the page, and not once per
 * row: `KeyConfig::getById()` queries on a cache miss, so a per-row read would cost a cold page of
 * N keys N statements.
 *
 * @internal
 */
final class KeyGroupRelationServiceTest extends Unit
{
    use LabelResolverMockTrait;

    private const int STORE_ID = 1;

    private const int GROUP_ID = 7;

    private const int LOCALIZED_KEY_ID = 11;

    private const int NOT_LOCALIZED_KEY_ID = 12;

    private const int INHERITING_KEY_ID = 13;

    private const int UNRESOLVABLE_KEY_ID = 14;

    /**
     * @var list<array<int>>
     */
    private array $keyConfigReads = [];

    public function _before(): void
    {
        $this->keyConfigReads = [];
        $this->labelResolverCalls = [];
    }

    /**
     * @throws Exception
     */
    public function testEveryListedRelationCarriesTheLocalizationFlagOfItsKey(): void
    {
        $relations = $this->getKeyGroupRelations();

        $this->assertCount(4, $relations);
        $this->assertTrue($relations[0]->getLocalized());
        $this->assertFalse($relations[1]->getLocalized());
        $this->assertNull($relations[2]->getLocalized());
    }

    /**
     * A relation whose key the configuration no longer carries reports nothing, rather than taking
     * the whole listing down with it.
     *
     * @throws Exception
     */
    public function testARelationWhoseKeyCannotBeResolvedReportsNoLocalizationFlag(): void
    {
        $this->assertNull($this->getKeyGroupRelations()[3]->getLocalized());
    }

    /**
     * The labels of the whole page are warmed in one read, before any row is hydrated, so the
     * per-row title lookups that follow cost no statement of their own.
     *
     * @throws Exception
     */
    public function testTheLabelsOfThePageArePreloadedInOneRead(): void
    {
        $this->getKeyGroupRelations();

        $this->assertSame(
            [[
                self::LOCALIZED_KEY_ID,
                self::NOT_LOCALIZED_KEY_ID,
                self::INHERITING_KEY_ID,
                self::UNRESOLVABLE_KEY_ID,
            ]],
            $this->preloadedIds()
        );

        $this->assertSame(
            'preload',
            $this->calledMethods()[0],
            'a title read before the warm-up would be the per-row statement the preload exists to avoid'
        );
    }

    /**
     * @throws Exception
     */
    public function testEveryListedRelationCarriesTheTitleOfItsKey(): void
    {
        $relations = $this->getKeyGroupRelations();

        $this->assertSame('Key ' . self::LOCALIZED_KEY_ID, $relations[0]->getKeyTitle());
        $this->assertNull(
            $relations[3]->getKeyTitle(),
            'a relation whose key the configuration no longer carries reports no title'
        );
    }

    /**
     * The key configurations of the whole page are read in ONE statement, naming every key the page
     * lists: both the `localized` flag and the title the rows carry come out of it. A lookup per
     * relation is the N+1 this guards - it is invisible in the published payload, and paging this
     * very dialog is already slow.
     *
     * @throws Exception
     */
    public function testTheKeyConfigurationsOfThePageAreReadInOneStatement(): void
    {
        $this->getKeyGroupRelations();

        $this->assertSame(
            [[
                self::LOCALIZED_KEY_ID,
                self::NOT_LOCALIZED_KEY_ID,
                self::INHERITING_KEY_ID,
                self::UNRESOLVABLE_KEY_ID,
            ]],
            $this->keyConfigReads,
            'the key configurations of the page must cost one read, not one per relation'
        );
    }

    /**
     * A core that does not carry the flag reports none for any key: there the answer could only be
     * null. The page is still read once, because the title rides the same statement.
     *
     * @throws Exception
     */
    public function testACoreWithoutTheFlagReportsNoneForAnyKey(): void
    {
        $relations = $this->getKeyGroupRelations(new InMemoryKeyLocalizationResolver(false));

        $this->assertCount(4, $relations);

        foreach ($relations as $relation) {
            $this->assertNull($relation->getLocalized());
        }

        $this->assertCount(1, $this->keyConfigReads);
    }

    /**
     * @return KeyGroupRelation[]
     *
     * @throws Exception
     */
    private function getKeyGroupRelations(?InMemoryKeyLocalizationResolver $keyLocalizationResolver = null): array
    {
        $keyLocalizationResolver ??= new InMemoryKeyLocalizationResolver();

        $flags = [
            self::LOCALIZED_KEY_ID => true,
            self::NOT_LOCALIZED_KEY_ID => false,
            self::INHERITING_KEY_ID => null,
        ];

        $repository = $this->createMock(KeyGroupRelationRepositoryInterface::class);
        $repository
            ->method('getPaginatedKeyGroupRelationByStore')
            ->willReturn([
                $this->createRelation(self::LOCALIZED_KEY_ID),
                $this->createRelation(self::NOT_LOCALIZED_KEY_ID),
                $this->createRelation(self::INHERITING_KEY_ID),
                $this->createRelation(self::UNRESOLVABLE_KEY_ID),
            ]);
        $repository
            ->method('getKeyConfigs')
            ->willReturnCallback(
                // a key the configuration no longer carries has no row and is absent altogether
                function (array $keyIds) use ($flags, $keyLocalizationResolver): array {
                    $this->keyConfigReads[] = $keyIds;

                    return $this->createKeyConfigs($keyIds, $flags, $keyLocalizationResolver);
                }
            );

        $groupService = $this->createMock(GroupServiceInterface::class);
        $groupService
            ->method('getAllowedGroupIds')
            ->willReturn([]);

        $groupConfig = new GroupConfig();
        $groupConfig->setId(self::GROUP_ID);
        $groupConfig->setName('Measurements');

        $groupConfigResolver = $this->createMock(GroupConfigResolverInterface::class);
        $groupConfigResolver
            ->method('getById')
            ->willReturn($groupConfig);

        $labelResolver = $this->createLabelResolver();
        $labelLanguageResolver = $this->createLabelLanguageResolver('en', 'en');

        $service = new KeyGroupRelationService(
            $repository,
            $groupService,
            new KeyGroupRelationHydrator(
                $this->createMock(KeyFilterResolverInterface::class),
                $labelResolver,
                $labelLanguageResolver
            ),
            $groupConfigResolver,
            $this->createMock(EventDispatcherInterface::class),
            $keyLocalizationResolver,
            $labelResolver,
            $labelLanguageResolver,
        );

        return $service
            ->getKeyGroupRelations(
                new ListClassificationStoreParameter(self::STORE_ID, 'classificationStore', 1, 50)
            )
            ->getItems();
    }

    /**
     * The rows the one page read returns: a key the configuration no longer carries has none.
     *
     * @param int[] $keyIds
     * @param array<int, bool|null> $flags
     *
     * @return array<int, KeyConfig>
     */
    private function createKeyConfigs(
        array $keyIds,
        array $flags,
        InMemoryKeyLocalizationResolver $keyLocalizationResolver
    ): array {
        $keyConfigs = [];

        foreach ($keyIds as $keyId) {
            if ($keyId === self::UNRESOLVABLE_KEY_ID) {
                continue;
            }

            $keyConfig = $this->createKeyConfig($keyId, 'key' . $keyId, 'Key ' . $keyId);
            $keyLocalizationResolver->setLocalized($keyConfig, $flags[$keyId] ?? null);
            $keyConfigs[$keyId] = $keyConfig;
        }

        return $keyConfigs;
    }

    private function createRelation(int $keyId): CoreKeyGroupRelation
    {
        $relation = new CoreKeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName('key' . $keyId);
        $relation->setDescription('');

        return $relation;
    }
}
