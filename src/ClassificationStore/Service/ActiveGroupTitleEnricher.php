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

use Pimcore\Bundle\StaticResolverBundle\Models\DataObject\ClassificationStore\KeyConfigResolverInterface;
use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\LabelEntityType;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use function array_column;

/**
 * @internal
 */
final readonly class ActiveGroupTitleEnricher implements ActiveGroupTitleEnricherInterface
{
    public function __construct(
        private LabelResolverInterface $labelResolver,
        private KeyConfigResolverInterface $keyConfigResolver,
        private LabelLanguageResolverInterface $labelLanguageResolver,
    ) {
    }

    public function enrich(Data|Layout|null $layout): void
    {
        if ($layout instanceof Classificationstore) {
            $this->fillTitles($layout);

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

    private function fillTitles(Classificationstore $fieldDefinition): void
    {
        $language = $this->labelLanguageResolver->getLabelLanguage();

        // Post-processes a payload core owns; ActiveGroupTitleEnricherTest fails once a slot comes back empty.
        foreach ($fieldDefinition->activeGroupDefinitions as $index => $group) {
            /** @var array{name: string, keys: array<int|string, array{name: string, id?: int}>} $group */
            // classificationstore_groups has no title column: the title is the group name until package 16 lands.
            $group['title'] = $group['name'];

            // One statement for the whole group, instead of a label query per key.
            $this->labelResolver->preload(LabelEntityType::Key, array_column($group['keys'], 'id'), $language);

            foreach ($group['keys'] as $keyIndex => $key) {
                $group['keys'][$keyIndex]['title'] = $this->resolveKeyTitle($key, $language);
            }

            $fieldDefinition->activeGroupDefinitions[$index] = $group;
        }
    }

    /**
     * @param array{name: string, id?: int} $key
     */
    private function resolveKeyTitle(array $key, string $language): string
    {
        if (!isset($key['id'])) {
            return $key['name'];
        }

        $keyConfig = $this->keyConfigResolver->getById($key['id']);
        if (!$keyConfig instanceof KeyConfig) {
            return $key['name'];
        }

        // Falls back to the name only on an unset title, so a resolved '0' survives.
        return $this->labelResolver->getTitle($keyConfig, $language) ?? $key['name'];
    }
}
