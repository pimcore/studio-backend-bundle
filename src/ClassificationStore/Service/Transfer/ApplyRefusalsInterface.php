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
     * A key entry that would put a field type withdrawn as a key type - as its own type or as its
     * wrapper delegate - where the stored key does not already carry it, which is the refusal core's
     * key save raises and would otherwise surface as a failed save. `$target` is the stored key the
     * entry was matched to, whose own withdrawn types stay saveable as they do in core.
     */
    public function refuseWithdrawnKeyType(
        ApplyContext $context,
        ?KeyConfig $target,
        ArtifactKeyEntry $keyEntry,
        string $label,
    ): bool;

    /**
     * A key entry whose wrapper delegate is a field type a key is never defined with, which core's
     * key save refuses before it asks what the engine can hold.
     */
    public function refuseDelegateThatIsNotAKeyType(
        ApplyContext $context,
        ArtifactKeyEntry $keyEntry,
        string $label,
    ): bool;

    /**
     * A key whose field type the storage engine of the store the apply writes into cannot hold,
     * which is the refusal core's own key save raises for it and would otherwise surface as a failed
     * save. Asked only of an entry the apply would write: a key the store already carries as the
     * file describes it is left alone, as core leaves a row it is not writing alone.
     */
    public function refuseKeyTypeTheEngineCannotHold(
        ApplyContext $context,
        ArtifactKeyEntry $keyEntry,
        string $label,
    ): bool;

    /**
     * A key entry whose DECLARATION needs an engine capability the store the apply writes into does
     * not offer - a `multiValue` key, whose value is addressed under qualifiers the EAV value row
     * has no column for. The refusal core's own key save raises for it, named here instead of
     * surfacing as a failed save.
     *
     * The artifact deliberately carries no engine
     * ({@see ArtifactEntryMapperInterface}), so importing a file exported from a document store
     * into an EAV store is the one live way such a key still arrives after new stores are created
     * on the document engine. It reports as an ordinary conflict, in a dry run as in an apply.
     *
     * The target the entry was matched to is passed for the same reason the withdrawn refusal takes
     * it: core refuses only a configuration that introduces the need, so a key the store already
     * carries that way stays writable.
     */
    public function refuseDeclarationTheEngineCannotAddress(
        ApplyContext $context,
        ?KeyConfig $target,
        ArtifactKeyEntry $keyEntry,
        string $label,
    ): bool;

    /**
     * The refusals one key assignment carries on its own: a key the same file disables, a key the
     * target carries disabled and the file does not enable, an override outside the closed list the
     * model enforces, and a sub group neither the file nor the store carries.
     *
     * A disabled key is a conflict rather than a skip, whichever side holds the disabled state.
     * Core builds no active group definition from such a relation and deletes a key's assignments
     * only on the transition to disabled, so the row this would write is one nothing cleans up
     * again - and the file-disables-it case next to it is already a conflict, so the same end state
     * cannot be allowed to report differently for naming the key in one section fewer. A skip is
     * what an apply records where applying the entry would have meant guessing; this entry is not
     * ambiguous, it is one the target hides.
     */
    public function refuseAssignment(
        ApplyContext $context,
        ArtifactAssignmentEntry $assignment,
        string $label,
    ): bool;
}
