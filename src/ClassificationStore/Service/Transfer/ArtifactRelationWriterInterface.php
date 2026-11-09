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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Model\DataObject\Classificationstore\KeyGroupRelation;

/**
 * The join sections of an apply: one entry of a relation - collection to group, key to group -
 * against the row it was matched to, and the write it takes.
 *
 * Apart from the entity sections ({@see ArtifactEntryWriterInterface}, which publishes these two
 * operations as its own surface and delegates them here) because a relation is written from a pair
 * of endpoint identifiers this run has resolved rather than from a walked row of one level.
 *
 * @internal
 */
interface ArtifactRelationWriterInterface
{
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
     * One key assignment against the relation row it was matched to, or against none. An assignment
     * matched to none is a creation, and a creation seeds nothing from its key: `metadata` is a
     * free-form column the entry owns whole.
     *
     * @param array<string, mixed> $entry
     *
     * @throws ElementSavingFailedException
     * @throws InvalidArgumentException
     * @throws NotFoundException
     */
    public function writeKeyAssignment(
        ApplyContext $context,
        ?KeyGroupRelation $target,
        array $entry,
        string $entryKey,
    ): void;
}
