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

use Closure;
use Generator;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\KeyLocalizationResolverInterface;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;
use function count;
use function is_array;
use function is_string;
use function json_decode;
use function mb_strtolower;

/**
 * @internal
 */
final readonly class ArtifactTargetMatcher implements ArtifactTargetMatcherInterface
{
    private const int CHUNK_SIZE = 200;

    public function __construct(
        private KeyLocalizationResolverInterface $keyLocalizationResolver,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function matchEntryKey(
        array $entries,
        array $entriesByName,
        ?string $identifier,
        string $name,
    ): ?string {
        if ($identifier !== null) {
            return isset($entries[$identifier]) ? $identifier : null;
        }

        return $entriesByName[mb_strtolower($name)] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function indexByName(array $entries, string $field): array
    {
        $byName = [];

        foreach ($entries as $entryKey => $entry) {
            $name = $entry[$field] ?? null;

            if (!is_string($name)) {
                continue;
            }

            $byName[mb_strtolower($name)] ??= $entryKey;
        }

        return $byName;
    }

    /**
     * {@inheritdoc}
     */
    public function walkTargetByIdCursor(ApplyContext $context, Closure $walk): Generator
    {
        if ($context->getStoreId() === null) {
            return;
        }

        $afterId = 0;

        do {
            $targets = $walk($afterId, self::CHUNK_SIZE);

            foreach ($targets as $target) {
                $afterId = (int) $target->getId();

                yield $target;
            }
        } while (count($targets) === self::CHUNK_SIZE);
    }

    /**
     * {@inheritdoc}
     */
    public function walkTargetByRowCursor(ApplyContext $context, Closure $walk): Generator
    {
        if ($context->getStoreId() === null) {
            return;
        }

        $after = null;

        do {
            $targets = $walk($after, self::CHUNK_SIZE);

            foreach ($targets as $target) {
                $after = $target;

                yield $target;
            }
        } while (count($targets) === self::CHUNK_SIZE);
    }

    /**
     * {@inheritdoc}
     */
    public function describesSameEntity(
        ?string $storedName,
        ?string $storedDescription,
        string $name,
        ?string $description,
    ): bool {
        return $storedName === $name && ($storedDescription ?? '') === ($description ?? '');
    }

    /**
     * {@inheritdoc}
     *
     * Every field is compared in the form the write path stores it in, not in the form the file
     * carries it: a definition the entry leaves out or supplies as null is written as `[]` - the
     * key repository encodes `$entry->getDefinition() ?? []` - so comparing the parsed null against
     * the stored `[]` would report the key updated on every apply, and deploying one file twice
     * would never become a no-op. The title is normalized for the same reason: it is stored as `''`
     * when the entry leaves it out.
     *
     * `localized` is the one column compared without normalizing: an entry that leaves it out writes
     * NULL, so the parsed null and the stored NULL are already the same value, and collapsing it into
     * a boolean would report a key flagged false as unchanged against one that states nothing. It is
     * also the one column a core need not carry at all, and then it is not compared
     * ({@see self::describesSameLocalization()}).
     */
    public function describesSameKey(KeyConfig $target, ArtifactKeyEntry $entry): bool
    {
        $storedDefinition = json_decode($target->getDefinition(), true);

        return $target->getName() === $entry->getName()
            && ($target->getTitle() ?? '') === ($entry->getTitle() ?? '')
            && ($target->getDescription() ?? '') === ($entry->getDescription() ?? '')
            && $target->getType() === $entry->getType()
            && $target->getEnabled() === $entry->isEnabled()
            && $this->describesSameLocalization($target, $entry)
            && (is_array($storedDefinition) ? $storedDefinition : []) === ($entry->getDefinition() ?? []);
    }

    /**
     * {@inheritdoc}
     *
     * `sorter` and `mandatory` are always compared; a per assignment column only when the entry
     * supplies it, because an entry that leaves one out does not write it either.
     */
    public function describesSameAssignment(KeyGroupRelation $target, ArtifactAssignmentEntry $entry): bool
    {
        if ($target->getSorter() !== $entry->getSorter() || $target->isMandatory() !== $entry->isMandatory()) {
            return false;
        }

        if ($entry->isMetadataSupplied() && $target->getMetadata() !== $entry->getMetadata()) {
            return false;
        }

        if ($entry->isDefinitionOverrideSupplied()
            && $target->getDefinitionOverride() !== $entry->getDefinitionOverride()) {
            return false;
        }

        return !$entry->isSubGroupSupplied() || $target->getSubGroup() === $entry->getSubGroup();
    }

    /**
     * {@inheritdoc}
     */
    public function readNullableString(array $entry, string $field): ?string
    {
        $value = $entry[$field] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * On a core that carries the flag this is the strict tri-state comparison: `true`, `false` and
     * "states none" are three different answers and each has to match.
     *
     * On a core that does not carry it the column is not compared at all. There the stored flag reads
     * null whatever the key says and a write of it is dropped, so comparing would report every entry
     * that carries `localized: true` or `false` as a change the apply cannot make: each apply would
     * save the key again and report UPDATED, and deploying one file twice would never become a no-op
     * ({@see ArtifactEntryWriterInterface::writeKey()}).
     */
    private function describesSameLocalization(KeyConfig $target, ArtifactKeyEntry $entry): bool
    {
        if (!$this->keyLocalizationResolver->supportsLocalization()) {
            return true;
        }

        return $this->keyLocalizationResolver->getLocalized($target) === $entry->getLocalized();
    }
}
