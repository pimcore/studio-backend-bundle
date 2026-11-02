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

use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Trait\KeyLabelResolverTrait;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;

/**
 * @internal
 */
final readonly class ActiveGroupTitleEnricher implements ActiveGroupTitleEnricherInterface
{
    use KeyLabelResolverTrait;

    private const string KEYS = 'keys';

    public function __construct(
        private KeyConfigResolverInterface $keyConfigResolver,
        private LabelResolverInterface $labelResolver,
        private SecurityServiceInterface $securityService,
        private ToolResolverInterface $toolResolver,
    ) {
    }

    public function enrich(Data|Layout|null $layout): void
    {
        $fieldDefinitions = $this->collectClassificationStores($layout);

        if ($fieldDefinitions === []) {
            return;
        }

        $language = $this->resolveLabelLanguage($this->securityService, $this->toolResolver);
        // one statement for every key of every active group, so a 121-key group costs no query per key
        $this->preloadKeyLabels($this->labelResolver, $this->collectKeyIds($fieldDefinitions), $language);

        foreach ($fieldDefinitions as $fieldDefinition) {
            $this->fillTitles($fieldDefinition, $language);
        }
    }

    /**
     * @return Classificationstore[]
     */
    private function collectClassificationStores(Data|Layout|null $layout): array
    {
        if ($layout instanceof Classificationstore) {
            return [$layout];
        }

        if (!$layout instanceof Layout && !$layout instanceof Localizedfields) {
            return [];
        }

        $fieldDefinitions = [];
        foreach ($layout->getChildren() as $child) {
            if ($child instanceof Data || $child instanceof Layout) {
                $fieldDefinitions = [...$fieldDefinitions, ...$this->collectClassificationStores($child)];
            }
        }

        return $fieldDefinitions;
    }

    /**
     * @param Classificationstore[] $fieldDefinitions
     *
     * @return int[]
     */
    private function collectKeyIds(array $fieldDefinitions): array
    {
        $keyIds = [];
        foreach ($fieldDefinitions as $fieldDefinition) {
            /** @var array{keys: array<int|string, array{id: int}>} $group */
            foreach ($fieldDefinition->activeGroupDefinitions as $group) {
                foreach ($group[self::KEYS] as $key) {
                    $keyIds[] = $key['id'];
                }
            }
        }

        return $keyIds;
    }

    private function fillTitles(Classificationstore $fieldDefinition, string $language): void
    {
        // Post-processes a payload core owns; ActiveGroupTitleEnricherTest fails once a slot comes back empty.
        foreach ($fieldDefinition->activeGroupDefinitions as $index => $group) {
            /** @var array{name: string, keys: array<int|string, array{name: string, id: int}>} $group */
            // classificationstore_groups has no title column: the title is the group name until package 16 lands.
            $group['title'] = $group['name'];

            foreach ($group[self::KEYS] as $keyIndex => $key) {
                $group[self::KEYS][$keyIndex]['title'] = $this->resolveKeyTitle($key, $language);
            }

            $fieldDefinition->activeGroupDefinitions[$index] = $group;
        }
    }

    /**
     * @param array{name: string, id: int} $key
     */
    private function resolveKeyTitle(array $key, string $language): string
    {
        // The label the layout endpoints read, so a key is labelled the same before and after a reload.
        $title = $this->resolveStoredKeyTitle(
            $this->keyConfigResolver,
            $this->labelResolver,
            $key['id'],
            $language
        );

        // Falls back to the name only on an unset or empty title, so a stored '0' survives.
        return $title !== null && $title !== '' ? $title : $key['name'];
    }
}
