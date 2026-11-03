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
    }

    public function resolve(ArtifactSection $section, string $identifier): ?int
    {
        return $this->resolved[$section->value][$identifier] ?? null;
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
