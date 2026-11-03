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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The other half of an apply: one entry of the file against the row it was matched to - what that
 * is, created, updated, adopted, unchanged or refused, and the write it takes.
 *
 * Which rows there are to match is the reconciler's ({@see ReconcilerInterface}), and what matches
 * is the matcher's ({@see ArtifactTargetMatcherInterface}).
 *
 * @internal
 */
interface ArtifactEntryWriterInterface
{
    /**
     * The entity sections that carry a name and a description only, which is sub groups,
     * collections and groups: the store's rows of the level are walked, each matched against the
     * section, and every entry the walk did not reach is created.
     *
     * `$walk` reads one chunk of rows, by cursor; `$save` writes one entry onto the row it was
     * matched to, or onto a new row, and hands back its id.
     *
     * @throws DatabaseException
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function reconcileNamedEntities(
        ApplyContext $context,
        ArtifactSection $section,
        ArtifactDocument $document,
        Closure $walk,
        Closure $save,
    ): void;

    /**
     * One key entry against the key row it was matched to, or against none.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function writeKey(ApplyContext $context, ?KeyConfig $target, array $entry): void;

    /**
     * One collection to group relation the target does not carry.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function createCollectionRelation(ApplyContext $context, string $entryKey, array $entry): void;

    /**
     * One key assignment against the relation row it was matched to, or against none.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     */
    public function writeKeyAssignment(
        ApplyContext $context,
        ?KeyGroupRelation $target,
        array $entry,
        string $entryKey,
    ): void;
}
