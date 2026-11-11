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
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\ServiceResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ConcreteObjectResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\MappedParameter\LayoutParameter;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\ActiveGroupTitleEnricher;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyGroupLayoutService;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyQualifierResolver;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyRelationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\LabelLanguageResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;

/**
 * A key is labelled the same whether its group was already active when the object opened - the
 * object layout, enriched in place - or was added through "Add group", which is served by the
 * classification store layout endpoints. Both run over the same key configuration and the same
 * resolver here, so a divergence between them fails rather than being noticed in the editor.
 *
 * @internal
 */
final class KeyLabelPathAgreementTest extends Unit
{
    use LabelResolverMockTrait;

    private const int KEY_ID = 11;

    private const int GROUP_ID = 3;

    private const string KEY_NAME = 'weight';

    private const string LANGUAGE = 'de';

    private const string DEFAULT_LANGUAGE = 'en';

    private const string LABEL = 'Gewicht';

    private const string COLUMN_TITLE = 'Column title';

    /**
     * @throws Exception
     */
    public function testBothPathsPublishTheLabelRowOfTheUsersLanguage(): void
    {
        [$enriched, $published] = $this->labelFromBothPaths(
            self::COLUMN_TITLE,
            [self::KEY_ID => [self::LANGUAGE => self::LABEL]]
        );

        $this->assertSame(self::LABEL, $enriched);
        $this->assertSame($enriched, $published);
    }

    /**
     * @throws Exception
     */
    public function testBothPathsPublishTheTitleColumnWithoutALabelRow(): void
    {
        [$enriched, $published] = $this->labelFromBothPaths(self::COLUMN_TITLE, []);

        $this->assertSame(self::COLUMN_TITLE, $enriched);
        $this->assertSame($enriched, $published);
    }

    /**
     * The one deliberate divergence: with nothing stored anywhere the object layout publishes the
     * technical name, because an editor must not render a blank label, while the configuration
     * endpoints publish null, so they can still tell "no title set" apart from "title equals name".
     *
     * @throws Exception
     */
    public function testWithNothingStoredOnlyTheObjectLayoutFallsBackToTheName(): void
    {
        [$enriched, $published] = $this->labelFromBothPaths('', []);

        $this->assertSame(self::KEY_NAME, $enriched);
        $this->assertNull($published);
    }

    /**
     * Reopening the object resolves the same label again; nothing about the first pass is carried
     * into the payload the second one reads.
     *
     * @throws Exception
     */
    public function testEnrichingTheSamePayloadTwiceKeepsTheLabel(): void
    {
        $labels = [self::KEY_ID => [self::LANGUAGE => self::LABEL]];
        $enricher = new ActiveGroupTitleEnricher(
            $this->createLabelResolver($labels),
            $this->createDefinitionCacheResolver(self::COLUMN_TITLE),
            $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE)
        );

        $fieldDefinition = $this->createFieldDefinition();
        $enricher->enrich($fieldDefinition);
        $enricher->enrich($fieldDefinition);

        $this->assertSame(self::LABEL, $this->keyTitle($fieldDefinition));
    }

    /**
     * @param array<int, array<string, string>> $labels
     *
     * @return array{0: mixed, 1: ?string} the title of the object layout, then of the CS layout
     *
     * @throws Exception
     */
    private function labelFromBothPaths(string $titleColumn, array $labels): array
    {
        $labelResolver = $this->createLabelResolver($labels);
        $keyConfigResolver = $this->createKeyConfigResolver($titleColumn);
        $definitionCacheResolver = $this->createDefinitionCacheResolver($titleColumn);
        $labelLanguageResolver = $this->createLabelLanguageResolver(self::LANGUAGE, self::DEFAULT_LANGUAGE);

        $fieldDefinition = $this->createFieldDefinition();
        (new ActiveGroupTitleEnricher($labelResolver, $definitionCacheResolver, $labelLanguageResolver))
            ->enrich($fieldDefinition);

        $keyLayout = $this
            ->createKeyGroupLayoutService($labelResolver, $keyConfigResolver, $labelLanguageResolver)
            ->getKeyLayout(new LayoutParameter('classificationStore'), self::KEY_ID, self::GROUP_ID);

        return [$this->keyTitle($fieldDefinition), $keyLayout->getTitle()];
    }

    private function createKeyConfigResolver(string $titleColumn): KeyConfigResolverInterface
    {
        $keyConfig = $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, $titleColumn);

        $keyConfigResolver = $this->createMock(KeyConfigResolverInterface::class);
        $keyConfigResolver
            ->method('getById')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return $keyConfigResolver;
    }

    /**
     * The enricher reads the key config from the definition cache the layout has already
     * filled; the layout service beside it still resolves through KeyConfigResolver. Both
     * must answer with the same KeyConfig, which is the agreement this test is about.
     */
    private function createDefinitionCacheResolver(string $titleColumn): DefinitionCacheResolverInterface
    {
        $keyConfig = $this->createKeyConfig(self::KEY_ID, self::KEY_NAME, $titleColumn);

        $definitionCacheResolver = $this->createMock(DefinitionCacheResolverInterface::class);
        $definitionCacheResolver
            ->method('get')
            ->with(self::KEY_ID)
            ->willReturn($keyConfig);

        return $definitionCacheResolver;
    }

    /**
     * @throws Exception
     */
    private function createKeyGroupLayoutService(
        LabelResolverInterface $labelResolver,
        KeyConfigResolverInterface $keyConfigResolver,
        LabelLanguageResolverInterface $labelLanguageResolver
    ): KeyGroupLayoutService {
        $relation = new KeyGroupRelation();
        $relation->setKeyId(self::KEY_ID);
        $relation->setGroupId(self::GROUP_ID);
        $relation->setName(self::KEY_NAME);
        $relation->setDescription('');
        $relation->setDefinition('{"fieldtype":"input"}');
        $relation->setType('input');

        $keyRelationResolver = $this->createMock(KeyRelationResolverInterface::class);
        $keyRelationResolver
            ->method('getByGroupAndKey')
            ->with(self::GROUP_ID, self::KEY_ID)
            ->willReturn($relation);

        $serviceResolver = $this->createMock(ServiceResolverInterface::class);
        $serviceResolver
            ->method('getFieldDefinitionFromJson')
            ->willReturn(new Input());

        return new KeyGroupLayoutService(
            $serviceResolver,
            $keyRelationResolver,
            new ConcreteObjectResolver(),
            $keyConfigResolver,
            $labelResolver,
            $labelLanguageResolver,
            new InMemoryKeyLocalizationResolver(),
            new KeyQualifierResolver($serviceResolver)
        );
    }

    private function createFieldDefinition(): Classificationstore
    {
        $definition = new Input();
        $definition->setName(self::KEY_NAME);
        $definition->setTitle('Definition title');

        $fieldDefinition = new Classificationstore();
        $fieldDefinition->setName('classificationStore');
        $fieldDefinition->activeGroupDefinitions = [
            self::GROUP_ID => [
                'name' => 'Technical',
                'id' => self::GROUP_ID,
                'description' => '',
                'keys' => [
                    [
                        'name' => self::KEY_NAME,
                        'id' => self::KEY_ID,
                        'description' => '',
                        'definition' => $definition,
                    ],
                ],
            ],
        ];

        return $fieldDefinition;
    }

    private function keyTitle(Classificationstore $fieldDefinition): mixed
    {
        return $fieldDefinition->activeGroupDefinitions[self::GROUP_ID]['keys'][0]['title'] ?? null;
    }
}
