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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use function array_column;
use function array_keys;

/**
 * Guards the slots this bundle writes into the activeGroupDefinitions payload core owns: a change to
 * the literal at Classificationstore::setActiveGroupDefinitions() empties them, and these assertions
 * fail instead of the editor silently rendering blank labels.
 *
 * @internal
 */
final class ActiveGroupTitleEnricherTest extends Unit
{
    use LabelResolverMockTrait;

    private const int GROUP_ID = 3;

    private const int SECOND_GROUP_ID = 4;

    private const string GROUP_NAME = 'Technical';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const int KEY_ID = 11;

    private const string KEY_NAME = 'weight';

    private const array KEY_NAMES = [11 => self::KEY_NAME, 12 => 'height', 13 => 'depth', 21 => 'claim'];

    private const array LABELS = [11 => 'Weight', 12 => 'Height', 13 => 'Depth', 21 => 'Claim'];

    private const array EXPECTED_KEY_TITLES = ['Weight', 'Height', 'Depth'];

    private const string COLUMN_TITLE = 'Column title';

    private const string DEFINITION_TITLE = 'Definition title';

    public function testEveryKeyOfAnActiveGroupCarriesATitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(
            self::EXPECTED_KEY_TITLES,
            $this->keyTitles($fieldDefinition, self::GROUP_ID)
        );
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testEveryActiveGroupCarriesItsNameAsTitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(self::GROUP_NAME, $this->groupTitle($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * Core reindexes activeGroupDefinitions with usort() whenever a collection mapping exists, so the
     * payload is group-id-keyed sometimes and zero-indexed other times.
     */
    public function testAZeroIndexedPayloadIsEnrichedTheSameWay(): void
    {
        $fieldDefinition = $this->createFieldDefinition([$this->createGroup()]);

        $this->createEnricher()->enrich($fieldDefinition);

        $this->assertSame(self::GROUP_NAME, $this->groupTitle($fieldDefinition, 0));
        $this->assertSame(self::EXPECTED_KEY_TITLES, $this->keyTitles($fieldDefinition, 0));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAClassificationStoreNestedInTheLayoutIsReached(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher()->enrich($this->nestInALayout($fieldDefinition));

        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The whole point of the resolver: a group costs one label query, not one per key. Every group of
     * every classification store field of the layout gets its own, and no title of a group is resolved
     * before the preload that covers it.
     */
    public function testEveryGroupIsPreloadedOnceBeforeAnyOfItsTitlesIsResolved(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroup(),
            self::SECOND_GROUP_ID => $this->createGroupWithKeys([$this->createKey(21)]),
        ]);
        $nested = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $root = new Panel();
        $root->setChildren([$this->nestInALayout($fieldDefinition), $nested]);

        $this->createEnricher()->enrich($root);

        $this->assertSame([[11, 12, 13], [21], [self::KEY_ID]], $this->preloadedIds());
        $this->assertSame(
            [
                'preload', 'getTitle', 'getTitle', 'getTitle',
                'preload', 'getTitle',
                'preload', 'getTitle',
            ],
            $this->calledMethods()
        );
    }

    /**
     * The label the resolver answers with wins over the title carried by the serialized field
     * definition, which the enricher does not read at all any more.
     */
    public function testTheResolvedLabelIsPublishedInsteadOfTheDefinitionTitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($fieldDefinition);

        $this->assertSame([self::COLUMN_TITLE], $this->keyTitles($fieldDefinition, self::GROUP_ID));
    }

    public function testChangingOnlyTheDefinitionTitleChangesNothing(): void
    {
        $withTitle = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);
        $withoutTitle = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($withTitle);
        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($withoutTitle);

        $this->assertSame(
            $this->keyTitles($withoutTitle, self::GROUP_ID),
            $this->keyTitles($withTitle, self::GROUP_ID)
        );
    }

    /**
     * A resolved '0' is a configured label like any other: only an unresolved one falls back to the
     * key name.
     */
    public function testAKeyTitleOfZeroIsCarriedOver(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $this->createEnricher([self::KEY_ID => '0'])->enrich($fieldDefinition);

        $this->assertSame(['0'], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAKeyWithoutAnIdFallsBackToItsName(): void
    {
        $key = $this->createKey(self::KEY_ID);
        unset($key['id']);

        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$key]),
        ]);

        $this->createEnricher([self::KEY_ID => self::COLUMN_TITLE])->enrich($fieldDefinition);

        $this->assertSame([self::KEY_NAME], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAKeyWhoseConfigurationCannotBeLoadedFallsBackToItsName(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::LANGUAGE => self::COLUMN_TITLE]]),
            $this->createDefinitionCacheResolver([]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame([self::KEY_NAME], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The label row of the requesting user's language is what the editor sees, ahead of the title
     * column the chain ends on.
     */
    public function testTheLabelOfTheRequestingUsersLanguageIsPublished(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID, self::DEFINITION_TITLE),
            ]),
        ]);

        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::LANGUAGE => 'Gewicht']]),
            $this->createDefinitionCacheResolver([
                $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
            ]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame(['Gewicht'], $this->keyTitles($fieldDefinition, self::GROUP_ID));
    }

    /**
     * An installation with an empty labels table is unchanged from the release before: the key's
     * title column, and then its technical name.
     */
    public function testWithoutALabelRowTheTitleColumnAndThenTheNameIsPublished(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([
                $this->createKey(self::KEY_ID),
                $this->createKey(12),
            ]),
        ]);

        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver(),
            $this->createDefinitionCacheResolver([
                $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, self::COLUMN_TITLE),
                $this->createKeyConfig(12, self::KEY_NAMES[12], ''),
            ]),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame(
            [self::COLUMN_TITLE, self::KEY_NAMES[12]],
            $this->keyTitles($fieldDefinition, self::GROUP_ID)
        );
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * A layout read without a resolvable user is labelled in the installation default language
     * rather than left unlabelled.
     */
    public function testWithoutACurrentUserTheDefaultLanguageIsUsed(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(self::KEY_ID)]),
        ]);

        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver([self::KEY_ID => [self::DEFAULT_LANGUAGE => self::COLUMN_TITLE]]),
            $this->createDefinitionCacheResolver([$this->createKeyConfig(self::KEY_ID, self::KEY_NAME)]),
            $this->createLabelLanguageResolver(null, self::DEFAULT_LANGUAGE)
        );

        $enricher->enrich($fieldDefinition);

        $this->assertSame([self::COLUMN_TITLE], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertSame(
            [self::DEFAULT_LANGUAGE, self::DEFAULT_LANGUAGE],
            array_column($this->labelResolverCalls, 'language')
        );
    }

    /**
     * @param array<int, string> $labels key id => label stored for the user's language
     */
    private function createEnricher(array $labels = self::LABELS): ActiveGroupTitleEnricher
    {
        $labelsByKeyId = [];
        $keyConfigs = [];

        foreach (self::KEY_NAMES as $keyId => $keyName) {
            $keyConfigs[] = $this->createKeyConfig($keyId, $keyName);

            if (isset($labels[$keyId])) {
                $labelsByKeyId[$keyId] = [self::LANGUAGE => $labels[$keyId]];
            }
        }

        return new ActiveGroupTitleEnricher(
            $this->createLabelResolver($labelsByKeyId),
            $this->createDefinitionCacheResolver($keyConfigs),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );
    }

    /**
     * @param list<KeyConfig> $keyConfigs
     */
    private function createDefinitionCacheResolver(array $keyConfigs): DefinitionCacheResolverInterface
    {
        $byId = [];
        foreach ($keyConfigs as $keyConfig) {
            $byId[$keyConfig->getId() ?? 0] = $keyConfig;
        }

        $definitionCacheResolver = $this->createMock(DefinitionCacheResolverInterface::class);
        $definitionCacheResolver
            ->method('get')
            ->willReturnCallback(static fn (int $keyId): ?KeyConfig => $byId[$keyId] ?? null);

        return $definitionCacheResolver;
    }

    private function nestInALayout(Classificationstore $fieldDefinition): Panel
    {
        $localizedFields = new Localizedfields();
        $localizedFields->setChildren([$fieldDefinition]);

        $inner = new Panel();
        $inner->setChildren([new Input(), $localizedFields]);

        $root = new Panel();
        $root->setChildren([$inner]);

        return $root;
    }

    /**
     * @param array<int, array<string, mixed>> $activeGroupDefinitions
     */
    private function createFieldDefinition(array $activeGroupDefinitions): Classificationstore
    {
        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setName('classificationStore');
        $fieldDefinition->activeGroupDefinitions = $activeGroupDefinitions;

        return $fieldDefinition;
    }

    /**
     * @return array<string, mixed>
     */
    private function createGroup(): array
    {
        return $this->createGroupWithKeys([
            $this->createKey(self::KEY_ID),
            $this->createKey(12),
            $this->createKey(13),
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $keys
     *
     * @return array<string, mixed>
     */
    private function createGroupWithKeys(array $keys): array
    {
        return [
            'name' => self::GROUP_NAME,
            'id' => self::GROUP_ID,
            'description' => 'Technical attributes',
            'keys' => $keys,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createKey(int $keyId, ?string $definitionTitle = null): array
    {
        $definition = new Input();
        $definition->setName(self::KEY_NAMES[$keyId]);

        if ($definitionTitle !== null) {
            $definition->setTitle($definitionTitle);
        }

        return [
            'name' => self::KEY_NAMES[$keyId],
            'id' => $keyId,
            'description' => '',
            'definition' => $definition,
        ];
    }

    private function groupTitle(Classificationstore $fieldDefinition, int|string $groupIndex): mixed
    {
        return $fieldDefinition->activeGroupDefinitions[$groupIndex]['title'] ?? null;
    }

    /**
     * @return array<int, mixed>
     */
    private function keyTitles(Classificationstore $fieldDefinition, int|string $groupIndex): array
    {
        return array_column($fieldDefinition->activeGroupDefinitions[$groupIndex]['keys'], 'title');
    }

    private function assertNoSlotIsEmpty(Classificationstore $fieldDefinition): void
    {
        foreach (array_keys($fieldDefinition->activeGroupDefinitions) as $groupIndex) {
            $titles = [
                $this->groupTitle($fieldDefinition, $groupIndex),
                ...$this->keyTitles($fieldDefinition, $groupIndex),
            ];

            foreach ($titles as $title) {
                $this->assertNotNull($title);
                $this->assertNotSame('', $title);
            }
        }
    }
}
