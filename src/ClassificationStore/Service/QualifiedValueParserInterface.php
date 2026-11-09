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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * The request boundary of a qualified slot: what a write supplied, read into entries and refused
 * where it is not something the key can hold.
 *
 * Its own half of {@see QualifiedValueSerializerInterface}, which reads and assembles the slot
 * itself. The split is along the two readings the slot has - a supplied payload is read leniently
 * and a stored one strictly - so that the rules a write is held to live in one place, apart from
 * what the row holds.
 *
 * @internal
 */
interface QualifiedValueParserInterface
{
    /**
     * The entries a write supplies, validated: the shape, the variant names against the key's
     * declaration, the positions against its `multiValue` setting, and the uniqueness of the
     * (variant, position) pairs.
     *
     * A payload that is not a list of qualified entries is one entry of the default variant at
     * position 0, which is what an un-updated client sends. A list holding at least one entry
     * object - an array carrying `v` - is a list of entries; the array value of a multi-select or a
     * relation field is not, and such a value keeps travelling whole.
     *
     * The empty list is the empty *entry* list: no entry at all. It is what a key whose declaration
     * narrowed to hide every entry of its row publishes
     * ({@see QualifiedValueSerializerInterface::getVisibleEntries()} answers with none, so the read
     * emits `[]`), and reading it as one entry holding the empty array would have a PUT of what that
     * read published manufacture a default entry the row never had. A client clearing the default
     * slot of such a key to an empty array value names it: `[{"v": []}]`. The payload of a key that
     * declares nothing never reaches here, so its own empty array stays the value it has always
     * been.
     *
     * Every entry is held to the declaration, whatever the row already holds: a stored value the
     * declaration no longer permits is hidden by
     * {@see QualifiedValueSerializerInterface::getVisibleEntries()}, so a client saving back what a
     * read handed it never names one.
     *
     * @return QualifiedValue[]
     *
     * @throws InvalidArgumentException
     */
    public function parseEntries(mixed $payload, KeyConfig $keyConfig): array;
}
