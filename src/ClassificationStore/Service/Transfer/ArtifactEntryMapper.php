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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactField;
use Pimcore\Model\DataObject\Classificationstore\CollectionConfig;
use Pimcore\Model\DataObject\Classificationstore\GroupConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Pimcore\Model\DataObject\Classificationstore\SubGroupConfig;
use function is_array;
use function json_decode;

/**
 * @internal
 */
final readonly class ArtifactEntryMapper implements ArtifactEntryMapperInterface
{
    public function __construct(
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function mapStore(StoreConfig $store): array
    {
        return [
            ArtifactField::IDENTIFIER->value => $store->getIdentifier(),
            ArtifactField::NAME->value => $store->getName(),
            ArtifactField::DESCRIPTION->value => $store->getDescription(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapSubGroup(SubGroupConfig $config): array
    {
        return [
            ArtifactField::IDENTIFIER->value => $config->getIdentifier(),
            ArtifactField::NAME->value => $config->getName(),
            ArtifactField::DESCRIPTION->value => $config->getDescription(),
            ArtifactField::CUSTOM_METADATA->value => $config->getCustomMetadata(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapCollection(CollectionConfig $config): array
    {
        return [
            ArtifactField::IDENTIFIER->value => $config->getIdentifier(),
            ArtifactField::NAME->value => $config->getName(),
            ArtifactField::DESCRIPTION->value => $config->getDescription(),
            ArtifactField::CUSTOM_METADATA->value => $config->getCustomMetadata(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapGroup(GroupConfig $config): array
    {
        return [
            ArtifactField::IDENTIFIER->value => $config->getIdentifier(),
            ArtifactField::NAME->value => $config->getName(),
            ArtifactField::DESCRIPTION->value => $config->getDescription(),
            ArtifactField::CUSTOM_METADATA->value => $config->getCustomMetadata(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapKey(KeyConfig $config): array
    {
        $definition = json_decode($config->getDefinition(), true);

        $entry = [
            ArtifactField::IDENTIFIER->value => $config->getIdentifier(),
            ArtifactField::NAME->value => $config->getName(),
            ArtifactField::TITLE->value => $config->getTitle(),
            ArtifactField::DESCRIPTION->value => $config->getDescription(),
            ArtifactField::TYPE->value => $config->getType(),
            ArtifactField::DEFINITION->value => is_array($definition) ? $definition : null,
            ArtifactField::ENABLED->value => $config->getEnabled(),
            ArtifactField::CUSTOM_METADATA->value => $config->getCustomMetadata(),
        ];

        $localized = $this->keyLocalizationResolver->getLocalized($config);

        // left out rather than written as null: a key that states nothing is described by an entry
        // that says nothing, which is the entry a file written before the flag existed carries
        if ($localized !== null) {
            $entry[ArtifactField::LOCALIZED->value] = $localized;
        }

        return $entry;
    }

    /**
     * {@inheritdoc}
     */
    public function mapCollectionRelation(ScopedCollectionRelation $relation): array
    {
        return [
            ArtifactField::COLLECTION->value => $relation->getCollectionIdentifier(),
            ArtifactField::GROUP->value => $relation->getGroupIdentifier(),
            ArtifactField::SORTER->value => $relation->getRelation()->getSorter(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapKeyAssignment(ScopedKeyAssignment $assignment): array
    {
        $relation = $assignment->getRelation();

        return [
            ArtifactField::GROUP->value => $assignment->getGroupIdentifier(),
            ArtifactField::KEY->value => $assignment->getKeyIdentifier(),
            ArtifactField::SORTER->value => $relation->getSorter(),
            ArtifactField::MANDATORY->value => $relation->isMandatory(),
            ArtifactField::METADATA->value => $relation->getMetadata(),
            ArtifactField::DEFINITION_OVERRIDE->value => $relation->getDefinitionOverride(),
            // an empty sub group is no sub group: written as null, which is what the apply side reads it as
            ArtifactField::SUB_GROUP->value => $relation->getSubGroup() === '' ? null : $relation->getSubGroup(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function mapToAssignment(ArtifactAssignmentEntry $entry, int $keyId, int $groupId): KeyGroupRelation
    {
        $relation = new KeyGroupRelation();
        $relation->setKeyId($keyId);
        $relation->setGroupId($groupId);
        $relation->setSorter($entry->getSorter());
        $relation->setMandatory($entry->isMandatory());

        if ($entry->isMetadataSupplied()) {
            $relation->setMetadata($entry->getMetadata());
        }

        if ($entry->isDefinitionOverrideSupplied()) {
            $relation->setDefinitionOverride($entry->getDefinitionOverride());
        }

        if ($entry->isSubGroupSupplied()) {
            $relation->setSubGroup($entry->getSubGroup());
        }

        return $relation;
    }
}
