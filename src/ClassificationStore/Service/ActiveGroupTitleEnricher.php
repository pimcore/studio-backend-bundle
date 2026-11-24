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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service;

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\DefinitionCacheResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use function array_column;

/**
 * @internal
 */
final readonly class ActiveGroupTitleEnricher implements ActiveGroupTitleEnricherInterface
{
    // core's own fallback for a field definition whose store was never stored
    private const int DEFAULT_STORE_ID = 1;

    public function __construct(
        private LabelResolverInterface $labelResolver,
        private DefinitionCacheResolverInterface $definitionCacheResolver,
        private LabelLanguageResolverInterface $labelLanguageResolver,
        private KeyRelationResolverInterface $keyRelationResolver,
        private KeyGroupLayoutServiceInterface $keyGroupLayoutService,
        private SubGroupLayoutResolverInterface $subGroupLayoutResolver,
    ) {
    }

    public function enrich(Data|Layout|null $layout): void
    {
        if ($layout instanceof Classificationstore) {
            $this->fillGroups($layout);

            return;
        }

        if (!$layout instanceof Layout && !$layout instanceof Localizedfields) {
            return;
        }

        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Data || $child instanceof Layout) {
                $this->enrich($child);
            }
        }
    }

    /**
     * Post-processes a payload core owns, which carries the group and its keys but nothing the
     * sub groups and the qualified values are rendered from; GroupLayoutPathAgreementTest fails
     * once this says anything the layout endpoint does not say about the same group.
     */
    private function fillGroups(Classificationstore $fieldDefinition): void
    {
        if ($fieldDefinition->activeGroupDefinitions === []) {
            return;
        }

        $language = $this->labelLanguageResolver->getLabelLanguage();
        $storeId = $this->resolveStoreId($fieldDefinition);

        // One statement for every group of the field, instead of a relation query per group.
        $relations = $this->keyRelationResolver->getByGroups(
            array_column($fieldDefinition->activeGroupDefinitions, 'id')
        );

        foreach ($fieldDefinition->activeGroupDefinitions as $index => $group) {
            /** @var array{name: string, id?: int, keys: array<int|string, array{name: string, id?: int}>} $group */
            $groupRelations = $relations[$group['id'] ?? 0] ?? [];
            $keys = $group['keys'];

            // classificationstore_groups has no title column: the title is the group name until package 16 lands.
            $group['title'] = $group['name'];
            // the resolver the layout endpoint answers from, over the relations of the same group
            $group['subGroups'] = $this->subGroupLayoutResolver->resolve($groupRelations, $storeId);

            // One statement for the whole group, instead of a label query per key.
            $this->labelResolver->preload(LabelEntityType::Key, array_column($keys, 'id'), $language);

            foreach ($keys as $keyIndex => $key) {
                $keys[$keyIndex] = $this->fillKey($key, $groupRelations[$key['id'] ?? 0] ?? null, $language);
            }

            $group['keys'] = $keys;
            $fieldDefinition->activeGroupDefinitions[$index] = $group;
        }
    }

    /**
     * @param array{name: string, id?: int} $key
     *
     * @return array<string, mixed>
     */
    private function fillKey(array $key, ?KeyGroupRelation $relation, string $language): array
    {
        // one read of the key configuration for the label and the three qualifier members alike
        $keyConfig = $this->resolveKeyConfig($key);
        $qualifiers = $keyConfig instanceof KeyConfig
            ? $this->keyGroupLayoutService->getKeyQualifiers($keyConfig)
            : null;

        $key['title'] = $this->resolveKeyTitle($key, $keyConfig, $language);
        // the sub group belongs to the assignment rather than to the key, so it is read off the relation
        $key['subGroup'] = $relation?->getSubGroup();
        $key['variants'] = $qualifiers?->getVariants();
        $key['defaultVariant'] = $qualifiers?->getDefaultVariant();
        $key['defaultVariantLabel'] = $qualifiers?->getDefaultVariantLabel();

        return $key;
    }

    /**
     * @param array{name: string, id?: int} $key
     */
    private function resolveKeyConfig(array $key): ?KeyConfig
    {
        if (!isset($key['id'])) {
            return null;
        }

        // Reads the cache enrichLayoutDefinition() filled one line earlier in LayoutService;
        // KeyConfig::getById() checks RuntimeCache instead, which that batch never populates.
        $keyConfig = $this->definitionCacheResolver->get($key['id']);

        return $keyConfig instanceof KeyConfig ? $keyConfig : null;
    }

    /**
     * @param array{name: string, id?: int} $key
     */
    private function resolveKeyTitle(array $key, ?KeyConfig $keyConfig, string $language): string
    {
        if (!$keyConfig instanceof KeyConfig) {
            return $key['name'];
        }

        // Falls back to the name only on an unset title, so a resolved '0' survives.
        return $this->labelResolver->getTitle($keyConfig, $language) ?? $key['name'];
    }

    private function resolveStoreId(Classificationstore $fieldDefinition): int
    {
        // getStoreId() reads a typed property that a field definition built in memory leaves unset
        return isset($fieldDefinition->storeId)
            ? $fieldDefinition->getStoreId()
            : self::DEFAULT_STORE_ID;
    }
}
