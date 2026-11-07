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
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation as CoreKeyGroupRelation;
use ReflectionProperty;

/**
 * @internal
 */
final class KeyGroupRelationHydrator implements KeyGroupRelationHydratorInterface
{
    public function hydrate(
        CoreKeyGroupRelation $keyGroupRelation,
        GroupConfig $groupConfig,
        ?bool $keyLocalized = null,
    ): KeyGroupRelation {
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
        );
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
}
