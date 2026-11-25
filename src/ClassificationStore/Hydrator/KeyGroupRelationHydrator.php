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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\KeyGroupRelation;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyFilterResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\LabelLanguageResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\LabelResolverInterface;
use ReflectionProperty;

/**
 * @internal
 */
final class KeyGroupRelationHydrator implements KeyGroupRelationHydratorInterface
{
    public function __construct(
        private readonly KeyFilterResolverInterface $keyFilterResolver,
        private readonly LabelResolverInterface $labelResolver,
        private readonly LabelLanguageResolverInterface $labelLanguageResolver,
    ) {
    }

    public function hydrate(
        CoreKeyGroupRelation $keyGroupRelation,
        GroupConfig $groupConfig,
        ?bool $keyLocalized = null,
        ?KeyConfig $keyConfig = null,
    ): KeyGroupRelation {
        $filterStated = $this->statesTypeAndDefinition($keyGroupRelation);
        $filterReason = $filterStated ? $this->keyFilterResolver->getFilterUnavailableReason($keyGroupRelation) : null;

        return new KeyGroupRelation(
            keyId: $keyGroupRelation->getKeyId(),
            groupId: $groupConfig->getId(),
            keyName: $keyGroupRelation->getName(),
            groupName: $groupConfig->getName(),
            keyDescription: $keyGroupRelation->getDescription(),
            groupDescription: $groupConfig->getDescription(),
            localized: $keyLocalized,
            // off the relation already loaded, so no key configuration and no statement is added
            keyType: $this->readType($keyGroupRelation),
            filterable: $filterStated ? $filterReason === null : null,
            filterUnavailableReason: $filterReason?->value,
            keyTitle: $this->resolveKeyTitle($keyConfig),
        );
    }

    /**
     * The label the key is shown under, resolved for the label language.
     *
     * The relation itself carries no title: core's listing selects the key's columns but keeps only
     * those its model has a property for, and it has none for this one, so the caller hands the key
     * configuration of the whole page in - looking one up per relation is a statement per row. The
     * resolver walks the language chain down to the key's own title and then to its name, so no
     * fallback is repeated here; null stands for a relation whose key configuration is gone, which
     * the caller reads as "nothing better than the name".
     */
    private function resolveKeyTitle(?KeyConfig $keyConfig): ?string
    {
        if (!$keyConfig instanceof KeyConfig) {
            return null;
        }

        return $this->labelResolver->getTitle($keyConfig, $this->labelLanguageResolver->getLabelLanguage());
    }

    /**
     * The relation's own key type, or null where it does not state one.
     *
     * `KeyGroupRelation::$type` is a typed property with no default, so a relation that was never
     * hydrated with one throws rather than returning a value to fall back on - and a relation
     * without it is ordinary here, not an edge case: four of this bundle's own unit tests build one
     * that way. Checked before it is read, never caught afterwards, because an Error raised inside
     * the model would say nothing about which relation it came from.
     */
    private function readType(CoreKeyGroupRelation $keyGroupRelation): ?string
    {
        if (!(new ReflectionProperty(CoreKeyGroupRelation::class, 'type'))->isInitialized($keyGroupRelation)) {
            return null;
        }

        return $keyGroupRelation->getType();
    }

    /**
     * Whether the relation carries both the type and the definition a filter is decided from. Both are typed
     * properties without a default, so they are checked before they are read.
     */
    private function statesTypeAndDefinition(CoreKeyGroupRelation $keyGroupRelation): bool
    {
        return (new ReflectionProperty(CoreKeyGroupRelation::class, 'type'))->isInitialized($keyGroupRelation)
            && (new ReflectionProperty(CoreKeyGroupRelation::class, 'definition'))->isInitialized($keyGroupRelation);
    }
}
