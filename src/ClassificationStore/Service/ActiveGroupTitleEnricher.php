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

use Pimcore\Model\DataObject\ClassDefinition\Data;
use Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore;
use Pimcore\Model\DataObject\ClassDefinition\Data\Localizedfields;
use Pimcore\Model\DataObject\ClassDefinition\Layout;

/**
 * @internal
 */
final readonly class ActiveGroupTitleEnricher implements ActiveGroupTitleEnricherInterface
{
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
        // Post-processes a payload core owns; ActiveGroupTitleEnricherTest fails once a slot comes back empty.
        foreach ($fieldDefinition->activeGroupDefinitions as $index => $group) {
            /** @var array{name: string, keys: array<int|string, array{name: string, definition?: mixed}>} $group */
            // classificationstore_groups has no title column: the title is the group name until package 16 lands.
            $group['title'] = $group['name'];

            foreach ($group['keys'] as $keyIndex => $key) {
                $group['keys'][$keyIndex]['title'] = $this->resolveKeyTitle($key);
            }

            $fieldDefinition->activeGroupDefinitions[$index] = $group;
        }
    }

    /**
     * @param array{name: string, definition?: mixed} $key
     */
    private function resolveKeyTitle(array $key): string
    {
        $definition = $key['definition'] ?? null;
        // Falls back to the name so the editor never renders a blank label; getKeyTitle() stays verbatim.
        if ($definition instanceof Data && !empty($definition->getTitle())) {
            return $definition->getTitle();
        }

        return $key['name'];
    }
}
