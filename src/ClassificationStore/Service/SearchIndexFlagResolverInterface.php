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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ArtifactTargetMatcherInterface;

/**
 * @internal
 */
interface SearchIndexFlagResolverInterface
{
    /**
     * The reserved member that says whether a key assignment is in the search index: boolean `false`
     * excludes it, and anything else - `true`, a non boolean, an absent member, or a NULL `metadata`
     * column - leaves it indexed, which is what every assignment written so far is.
     *
     * The member is read in two places and spelled the same in both: inside an assignment's
     * `metadata`, where it is the assignment's own answer, and inside a key's `definition`, where it
     * is the default a created assignment of that key takes. `metadata` stays free-form either way,
     * so a non boolean is stored as it arrived rather than refused.
     *
     * Not to be confused with the `index` member a Pimcore field definition blob carries: that one
     * is about the object query tables, which classification store values never reach.
     *
     * Adding the member to a key's definition is a change to that definition like any other, so an
     * apply of a configuration file written before it reports the key as updated until the file
     * carries it as well ({@see ArtifactTargetMatcherInterface::describesSameKey()}).
     */
    public const string INDEXED_MEMBER = 'indexed';

    /**
     * Whether the assignment carrying this `metadata` is in the search index. Null is the NULL
     * column, which is indexed - as is any value but boolean `false`.
     *
     * @param array<string, mixed>|null $metadata
     */
    public function isIndexed(?array $metadata): bool;

    /**
     * Whether this `metadata` answers the question itself, which is what the key's default fills in
     * for when it does not. A member present as null counts as an answer.
     *
     * @param array<string, mixed>|null $metadata
     */
    public function carriesFlag(?array $metadata): bool;

    /**
     * The `metadata` of a created assignment with the default of its key merged into the members it
     * already carries, or null where the key states no default and nothing is to be seeded. The key
     * is named by its `definition` column, and only a `metadata` {@see self::carriesFlag()} reports
     * nothing for is asked about.
     *
     * @param array<string, mixed>|null $metadata the supplied members, null for an absent or null one
     *
     * @return array<string, mixed>|null
     */
    public function withKeyDefault(?array $metadata, string $keyDefinition): ?array;
}
