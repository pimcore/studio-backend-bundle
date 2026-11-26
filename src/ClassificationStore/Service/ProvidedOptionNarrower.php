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
use Pimcore\Model\DataObject\ClassDefinition\Data\Multiselect;
use Pimcore\Model\DataObject\ClassDefinition\Data\Select;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelationCache;
use function array_key_exists;
use function is_array;
use function is_string;

/**
 * The bundle's half of core's option-subset handling, and the only place it reaches the core
 * helpers that answer it. The counterpart core applies after the same enrichment is
 * {@see \Pimcore\Model\DataObject\ClassDefinition\Data\Classificationstore::enrichLayoutDefinition()},
 * and this is kept to its semantics: an already-assigned group and a just-added one must offer the
 * editor the same options.
 *
 * @internal
 */
final readonly class ProvidedOptionNarrower implements ProvidedOptionNarrowerInterface
{
    private const string OPTIONS = 'options';

    /**
     * {@inheritdoc}
     */
    public function narrow(Data $definition, KeyGroupRelation $relation): void
    {
        $answering = KeyGroupRelationCache::getAnsweringDefinition($definition);

        if (!$answering instanceof Select && !$answering instanceof Multiselect) {
            return;
        }

        // a dynamic list is resolved again on every edit and carries no subset, so core leaves it whole
        if ($answering->dynamicOptions || $answering->getOptions() === null) {
            return;
        }

        if (!array_key_exists(self::OPTIONS, $relation->getDefinitionOverride() ?? [])) {
            return;
        }

        $narrowed = $relation->applyOptionSubset($answering->getOptions());
        $answering->setOptions($narrowed);

        $this->reconcileDefaultValue($answering, $narrowed);
    }

    /**
     * The two types declare the member differently, so what core's reconciliation hands back is
     * narrowed to each one's own type - which drops nothing: a select reconciles a string and a
     * multiselect a list.
     *
     * @param array<int, array<string, mixed>> $options
     */
    private function reconcileDefaultValue(Select|Multiselect $definition, array $options): void
    {
        $defaultValue = KeyGroupRelation::reconcileDefaultValue($definition->getDefaultValue(), $options);

        if ($definition instanceof Select) {
            $definition->setDefaultValue(is_string($defaultValue) ? $defaultValue : null);

            return;
        }

        $definition->setDefaultValue(is_array($defaultValue) ? $defaultValue : null);
    }
}
