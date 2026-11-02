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
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout\Panel;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use Pimcore\Model\UserInterface;
use function array_column;
use function array_keys;
use function range;
use function sprintf;

/**
 * Guards the slots this bundle writes into the activeGroupDefinitions payload core owns: a change to
 * the literal at Classificationstore::setActiveGroupDefinitions() empties them, and these assertions
 * fail instead of the editor silently rendering blank labels.
 *
 * @internal
 */
final class ActiveGroupTitleEnricherTest extends Unit
{
    private const int GROUP_ID = 3;

    private const string GROUP_NAME = 'Technical';

    private const array RESOLVED_LABELS = [11 => 'Weight', 12 => '', 13 => null];

    private const array EXPECTED_KEY_TITLES = ['Weight', 'height', 'depth'];

    private const string DEFINITION_TITLE = 'Definition title';

    private const string RESOLVED_LABEL = 'Gehäuse';

    private const string USER_LANGUAGE = 'de';

    /**
     * The title column of the key configuration, which the enricher does not read any more.
     */
    private const string COLUMN_TITLE = 'Column title';

    public function testEveryKeyOfAnActiveGroupCarriesATitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher(self::RESOLVED_LABELS)->enrich($fieldDefinition);

        $this->assertSame(
            self::EXPECTED_KEY_TITLES,
            $this->keyTitles($fieldDefinition, self::GROUP_ID)
        );
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testEveryActiveGroupCarriesItsNameAsTitle(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $this->createEnricher(self::RESOLVED_LABELS)->enrich($fieldDefinition);

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

        $this->createEnricher(self::RESOLVED_LABELS)->enrich($fieldDefinition);

        $this->assertSame(self::GROUP_NAME, $this->groupTitle($fieldDefinition, 0));
        $this->assertSame(self::EXPECTED_KEY_TITLES, $this->keyTitles($fieldDefinition, 0));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    public function testAClassificationStoreNestedInTheLayoutIsReached(): void
    {
        $fieldDefinition = $this->createFieldDefinition([self::GROUP_ID => $this->createGroup()]);

        $localizedFields = new Localizedfields();
        $localizedFields->setChildren([$fieldDefinition]);

        $inner = new Panel();
        $inner->setChildren([new Input(), $localizedFields]);

        $root = new Panel();
        $root->setChildren([$inner]);

        $this->createEnricher(self::RESOLVED_LABELS)->enrich($root);

        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * A resolved '0' is a label like any other: only an unset or empty one falls back to the key name.
     */
    public function testAKeyTitleOfZeroIsCarriedOver(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(11, 'weight')]),
        ]);

        $this->createEnricher([11 => '0'])->enrich($fieldDefinition);

        $this->assertSame(['0'], $this->keyTitles($fieldDefinition, self::GROUP_ID));
        $this->assertNoSlotIsEmpty($fieldDefinition);
    }

    /**
     * The payload carries the title of the key's enriched field definition, which is independent of the
     * label the layout endpoints read: the label is what both path families report.
     */
    public function testTheResolvedLabelWinsOverTheTitleOfTheFieldDefinition(): void
    {
        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys([$this->createKey(11, 'weight')]),
        ]);

        $this->createEnricher([11 => self::RESOLVED_LABEL])->enrich($fieldDefinition);

        $this->assertSame([self::RESOLVED_LABEL], $this->keyTitles($fieldDefinition, self::GROUP_ID));
    }

    /**
     * The labels of every active group are read with one call, before the first of them is resolved, so
     * an ETIM-sized group costs no query per key.
     */
    public function testTheLabelsOfEveryActiveGroupArePreloadedWithOneCall(): void
    {
        $keyIds = range(1, 121);
        $keys = [];
        foreach ($keyIds as $keyId) {
            $keys[] = $this->createKey($keyId, sprintf('key%d', $keyId));
        }

        $fieldDefinition = $this->createFieldDefinition([
            self::GROUP_ID => $this->createGroupWithKeys($keys),
            4 => $this->createGroupWithKeys([$this->createKey(200, 'extra')]),
        ]);

        $preloaded = false;
        $resolved = 0;

        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->expects($this->once())
            ->method('preload')
            ->with(
                $this->callback(static function (LabelEntityType $entityType) use (&$preloaded): bool {
                    $preloaded = true;

                    return $entityType === LabelEntityType::Key;
                }),
                [...$keyIds, 200],
                self::USER_LANGUAGE
            );
        $labelResolver
            ->method('getStoredTitle')
            ->willReturnCallback(function () use (&$preloaded, &$resolved): ?string {
                $this->assertTrue($preloaded, 'a key label was resolved before the preload');
                ++$resolved;

                return null;
            });

        $this->createEnricherWith($labelResolver)->enrich($fieldDefinition);

        $this->assertSame(122, $resolved);
    }

    /**
     * @param array<int, ?string> $resolvedLabels
     */
    private function createEnricher(array $resolvedLabels): ActiveGroupTitleEnricher
    {
        return $this->createEnricherWith($this->createLabelResolver($resolvedLabels));
    }

    private function createEnricherWith(LabelResolverInterface $labelResolver): ActiveGroupTitleEnricher
    {
        $user = $this->createMock(UserInterface::class);
        $user
            ->method('getLanguage')
            ->willReturn(self::USER_LANGUAGE);

        $securityService = $this->createMock(SecurityServiceInterface::class);
        $securityService
            ->method('getCurrentUser')
            ->willReturn($user);

        return new ActiveGroupTitleEnricher(
            $this->createKeyConfigResolver(),
            $labelResolver,
            $securityService,
            $this->createMock(ToolResolverInterface::class),
        );
    }

    /**
     * @param array<int, ?string> $resolvedLabels
     */
    private function createLabelResolver(array $resolvedLabels): LabelResolverInterface
    {
        $labelResolver = $this->createMock(LabelResolverInterface::class);
        $labelResolver
            ->method('getStoredTitle')
            ->willReturnCallback(
                function (KeyConfig $keyConfig, string $language) use ($resolvedLabels): ?string {
                    $this->assertSame(self::USER_LANGUAGE, $language);

                    return $resolvedLabels[$keyConfig->getId()] ?? null;
                }
            );

        return $labelResolver;
    }

    /**
     * The key configuration carries a title of its own; it is the resolver's last rung, not the
     * enricher's, so a disagreeing column never reaches the payload directly.
     */
    private function createKeyConfigResolver(): KeyConfigResolverInterface
    {
        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->willReturnCallback(static function (int $keyId): KeyConfig {
                $keyConfig = new KeyConfig();
                $keyConfig->setId($keyId);
                $keyConfig->setTitle(self::COLUMN_TITLE);

                return $keyConfig;
            });

        return $keyConfigResolver;
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
            $this->createKey(11, 'weight'),
            $this->createKey(12, 'height'),
            $this->createKey(13, 'depth'),
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
    private function createKey(int $keyId, string $name): array
    {
        $definition = new Input();
        $definition->setName($name);
        $definition->setTitle(self::DEFINITION_TITLE);

        return [
            'name' => $name,
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
