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
use function count;
use function sprintf;

/**
 * What an apply decided, counted in full and named up to a cap.
 *
 * The counts are the part that is always complete; the named lists stop at the cap and say how
 * many they left out, because a store of ETIM shape decides tens of thousands of entries and a
 * report that grew with them would be the one thing in this path that does.
 *
 * @internal
 */
final class ApplyReport
{
    public const int NAMED_ENTRY_CAP = 50;

    /**
     * @var array<string, int>
     */
    private array $counts = [];

    /**
     * @var array<string, list<string>>
     */
    private array $named = [];

    public function record(ApplyOutcome $outcome, string $entry): void
    {
        $key = $outcome->value;
        $this->counts[$key] = ($this->counts[$key] ?? 0) + 1;

        if (count($this->named[$key] ?? []) < self::NAMED_ENTRY_CAP) {
            $this->named[$key][] = $entry;
        }
    }

    public function getCount(ApplyOutcome $outcome): int
    {
        return $this->counts[$outcome->value] ?? 0;
    }

    /**
     * @return array<string, int>
     */
    public function getCounts(): array
    {
        $counts = [];
        foreach (ApplyOutcome::cases() as $outcome) {
            $counts[$outcome->value] = $this->getCount($outcome);
        }

        return $counts;
    }

    /**
     * The named entries of one category, with an explicit marker in place of what the cap left
     * out - part of what the report says, not a silent truncation.
     *
     * @return list<string>
     */
    public function getNamed(ApplyOutcome $outcome): array
    {
        $entries = $this->named[$outcome->value] ?? [];
        $left = $this->getCount($outcome) - count($entries);

        if ($left > 0) {
            $entries[] = sprintf('and %d more', $left);
        }

        return $entries;
    }

    public function hasConflicts(): bool
    {
        return $this->getCount(ApplyOutcome::CONFLICT) > 0;
    }
}
