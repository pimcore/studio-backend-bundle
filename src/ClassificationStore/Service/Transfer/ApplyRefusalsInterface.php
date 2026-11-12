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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Model\DataObject\Classificationstore\KeyConfig;

/**
 * What an apply refuses to write, and why: an entry whose write the level's own API would refuse,
 * or would carry out at the cost of configuration the file says nothing about.
 *
 * Each method answers one question - may this entry be written - and records the refusal on the
 * context when the answer is no, so that a dry run names it as well as a real run and a run names
 * every refusal rather than stopping at the first. The write itself is the writer's
 * ({@see ArtifactEntryWriterInterface}) and how a report reads is the diagnostics'
 * ({@see ApplyDiagnosticsInterface}).
 *
 * @internal
 */
interface ApplyRefusalsInterface
{
    /**
     * A name the level's own write path would refuse, which an artifact may not be written past
     * either. A file naming two identifiers the same way is a broken file and a conflict. A name a
     * stored row already carries under another identifier is the ambiguous case and is skipped: the
     * entry is recorded as skipped, with its identifier, and the stored row is left as it is.
     *
     * `$outcome` is what the entry would otherwise be, because the guard follows the entity rather
     * than the artifact - a rename is refused only where the level's own ::update() refuses one -
     * and `$matched` is the row the entry was matched to, which its own name never collides with, with
     * the name it is stored under.
     *
     * @throws DatabaseException
     */
    public function refuseTakenName(
        ApplyContext $context,
        ArtifactSection $section,
        ApplyOutcome $outcome,
        string $name,
        ?MatchedRow $matched,
        string $label,
        string $identifier,
    ): bool;

    /**
     * A key the file disables while the target still assigns it to groups, which would untag groups
     * the file says nothing about.
     */
    public function refuseDisablingAnAssignedKey(ApplyContext $context, KeyConfig $target, string $name): bool;

    /**
     * The refusals one key assignment carries on its own: a key the same file disables, an override
     * outside the closed list the model enforces, and a sub group neither the file nor the store
     * carries.
     */
    public function refuseAssignment(
        ApplyContext $context,
        ArtifactAssignmentEntry $assignment,
        string $label,
    ): bool;
}
