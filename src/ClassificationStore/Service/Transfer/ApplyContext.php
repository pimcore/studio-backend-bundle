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
    /**
     * @var array<string, array<string, int>>
     */
    private array $resolved = [];

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
     * @var array<string, array<string, bool>>
     */
    private array $claimedNames = [];

    /**
     * @var array<string, bool>
     */
    private array $disabledKeys = [];

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
        $this->plan($section, $identifier);
    }

    public function resolve(ArtifactSection $section, string $identifier): ?int
    {
        return $this->resolved[$section->value][$identifier] ?? null;
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

    /**
     * Takes the name for the entry, or reports that this run has already given it to another one.
     */
    public function claimName(ArtifactSection $section, string $name): bool
    {
        $claim = mb_strtolower($name);

        if (isset($this->claimedNames[$section->value][$claim])) {
            return false;
        }

        $this->claimedNames[$section->value][$claim] = true;

        return true;
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
