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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ArtifactSection;
use function mb_strtolower;
use function sprintf;

/**
 * The state one apply carries across its seven steps: the store it writes into, what it has
 * decided so far, and the row ids the identifiers of the earlier sections resolved to - a relation
 * can only be written once both of its endpoints are known by id.
 *
 * @internal
 */
final class ApplyContext
{
    private const string CLAIM_WRITTEN = 'written';

    private const string CLAIM_CARRIED = 'carried';

    /**
     * @var array<string, array<string, int>>
     */
    private array $resolved = [];

    /**
     * The same resolutions the other way round, row id to identifier, per level.
     *
     * A relation of the target names its endpoints by the identifiers their rows carry, and an
     * adopted row carries the file's identifier only once the adoption has been written - which a
     * dry run never does. Asking this instead of the row answers with the identifier the run has
     * decided the row has, in both modes, so a dry run reports an existing relation as the update
     * or the match it is rather than as target only and then created.
     *
     * @var array<string, array<int, string>>
     */
    private array $identifiersById = [];

    /**
     * The identifiers the run has decided will exist once it is done, whether or not it wrote a row
     * for them. A dry run writes nothing and so resolves no new id at all, which is why a planned
     * identifier is tracked beside the resolved ones rather than instead of them: a reference the
     * file itself declares is valid in both modes, and only a reference that is in neither the file
     * nor the store is the missing one apply has to refuse.
     *
     * @var array<string, array<string, bool>>
     */
    private array $planned = [];

    /**
     * The names this run has already answered for, per level, lowercased the way the name columns
     * are compared by the database the rows live in.
     *
     * A real run finds a second entry's collision in the row the first one left, so this is what a
     * dry run has in hand instead: it writes nothing, and a file naming two identifiers the same
     * way has to be refused in both modes rather than only once it is applied.
     *
     * @var array<string, array<string, string>>
     */
    private array $claimedNames = [];

    /**
     * The names this run is renaming rows away from, per level: the store still holds them until the
     * rename is written, and a dry run never writes it, so what asks the store has to ask this too.
     *
     * @var array<string, array<string, bool>>
     */
    private array $vacatedNames = [];

    /**
     * @var array<string, bool>
     */
    private array $disabledKeys = [];

    /**
     * The identifiers of the entries this run skipped, per level: an entry naming one of them is
     * skipped with it rather than refused for an endpoint that does not exist.
     *
     * @var array<string, array<string, bool>>
     */
    private array $skipped = [];

    private bool $storeCreated = false;

    private ?int $storeId = null;

    public function __construct(
        private readonly bool $dryRun,
        private readonly ApplyReport $report,
        private readonly ?Closure $onDecision = null,
    ) {
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    public function getReport(): ApplyReport
    {
        return $this->report;
    }

    public function getStoreId(): ?int
    {
        return $this->storeId;
    }

    public function setStoreId(?int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Whether this apply created the store, in which case no row of it predates the run and nothing
     * is skipped for sharing a name with one.
     */
    public function isStoreCreated(): bool
    {
        return $this->storeCreated;
    }

    public function setStoreCreated(bool $storeCreated): void
    {
        $this->storeCreated = $storeCreated;
    }

    /**
     * Counts the decision and hands it on as one line, so that a command can print the entries as
     * they are decided instead of holding them all.
     */
    public function record(ApplyOutcome $outcome, string $entry): void
    {
        $this->report->record($outcome, $entry);

        if ($this->onDecision !== null) {
            ($this->onDecision)(sprintf('%s: %s', $outcome->value, $entry));
        }
    }

    public function register(ArtifactSection $section, string $identifier, int $id): void
    {
        $this->resolved[$section->value][$identifier] = $id;
        $this->identifiersById[$section->value][$id] = $identifier;
        $this->plan($section, $identifier);
    }

    public function resolve(ArtifactSection $section, string $identifier): ?int
    {
        return $this->resolved[$section->value][$identifier] ?? null;
    }

    /**
     * The identifier this run has resolved for the row, or null for a row it has not registered.
     *
     * {@see self::$identifiersById} for why a relation reads its endpoints through this rather than
     * off the row it was read with.
     */
    public function identifierOf(ArtifactSection $section, int $id): ?string
    {
        return $this->identifiersById[$section->value][$id] ?? null;
    }

    /**
     * Records that the entry exists as far as the rest of the run is concerned, without an id.
     */
    public function plan(ArtifactSection $section, string $identifier): void
    {
        $this->planned[$section->value][$identifier] = true;
    }

    /**
     * Whether the entry is one this run has seen at all - planned, or resolved to a row.
     */
    public function isKnown(ArtifactSection $section, string $identifier): bool
    {
        return $this->planned[$section->value][$identifier] ?? false;
    }

    public function skip(ArtifactSection $section, string $identifier): void
    {
        $this->skipped[$section->value][$identifier] = true;
    }

    public function isSkipped(ArtifactSection $section, string $identifier): bool
    {
        return $this->skipped[$section->value][$identifier] ?? false;
    }

    /**
     * Takes the name for the entry, or reports that this run has already given it to another one.
     *
     * An entry whose matched row already carries the name ($carried) writes nothing new: any number of
     * those may share it, which is how a store already holding two rows of one name applies its own
     * export. It still conflicts with an entry that writes the name, in either order.
     */
    public function claimName(ArtifactSection $section, string $name, bool $carried = false): bool
    {
        $claim = mb_strtolower($name);
        $held = $this->claimedNames[$section->value][$claim] ?? null;

        if ($held !== null) {
            return $carried && $held === self::CLAIM_CARRIED;
        }

        $this->claimedNames[$section->value][$claim] = $carried ? self::CLAIM_CARRIED : self::CLAIM_WRITTEN;

        return true;
    }

    public function vacateName(ArtifactSection $section, string $name): void
    {
        $this->vacatedNames[$section->value][mb_strtolower($name)] = true;
    }

    public function isNameVacated(ArtifactSection $section, string $name): bool
    {
        return $this->vacatedNames[$section->value][mb_strtolower($name)] ?? false;
    }

    public function markKeyDisabled(string $identifier): void
    {
        $this->disabledKeys[$identifier] = true;
    }

    public function isKeyDisabled(string $identifier): bool
    {
        return $this->disabledKeys[$identifier] ?? false;
    }
}
