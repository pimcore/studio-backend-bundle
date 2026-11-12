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
     * The reserved member that says whether a key is in the search index: boolean `false` excludes it
     * wherever that key is used, and anything else - `true`, a non boolean, an absent member, or a
     * definition that does not parse - leaves it indexed, which is what every key written so far is.
     *
     * The member is read in one place and that place is the key's own `definition`. An assignment's
     * `metadata` reserves nothing: a member spelled `indexed` in there is an ordinary free-form
     * member that is stored as it arrived and changes nothing about the search index.
     *
     * What this bundle does with the flag is store it, publish it, queue a remap of the indices the
     * key's store reaches when it changes, and refuse a grid filter on a key it excludes; leaving the
     * key out of an index is the search index's own half of it.
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
     * Whether the key carrying this `definition` is in the search index. Null is a key that has no
     * definition yet, which is indexed - as is any value of the member but boolean `false`.
     *
     * @param string|null $keyDefinition the key's `definition` column, null for a key with none yet
     */
    public function isIndexed(?string $keyDefinition): bool;
}
