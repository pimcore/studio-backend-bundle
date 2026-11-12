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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Service\Transfer;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use function array_keys;
use function count;
use function sprintf;

/**
 * The counts are complete; the named lists stop at a cap and say how many they left out. A store of
 * ETIM shape decides tens of thousands of entries, and the report is the one part of an apply that
 * would otherwise grow with them.
 *
 * @internal
 */
final class ApplyReportTest extends Unit
{
    public function testTheCountsCoverEveryOutcome(): void
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::CREATED, 'group "a"');
        $report->record(ApplyOutcome::CREATED, 'group "b"');
        $report->record(ApplyOutcome::TARGET_ONLY, 'group "c"');

        $this->assertSame(
            ['created', 'updated', 'adopted', 'matchedUnchanged', 'targetOnly', 'skipped', 'conflicts'],
            array_keys($report->getCounts())
        );
        $this->assertSame(2, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame(1, $report->getCount(ApplyOutcome::TARGET_ONLY));
        $this->assertSame(0, $report->getCount(ApplyOutcome::UPDATED));
        $this->assertFalse($report->hasConflicts());
    }

    /**
     * Past the cap the list carries an explicit marker instead of simply ending, so that a reader
     * can tell a complete list from a capped one.
     *
     * Asserted on what the report says rather than against the cap itself, which is the report's own
     * implementation limit: the contract is that the list stops short of the count, names the
     * entries it kept in the order they were recorded, and accounts for every one it left out.
     */
    public function testTheNamedListIsCappedWithAnExplicitOverflowMarker(): void
    {
        $report = new ApplyReport();
        $recorded = 500;

        for ($i = 0; $i < $recorded; $i++) {
            $report->record(ApplyOutcome::CREATED, sprintf('key "k%d"', $i));
        }

        $named = $report->getNamed(ApplyOutcome::CREATED);
        $kept = count($named) - 1;

        $this->assertSame($recorded, $report->getCount(ApplyOutcome::CREATED));
        $this->assertLessThan($recorded, count($named));
        $this->assertSame('key "k0"', $named[0]);
        $this->assertSame(sprintf('key "k%d"', $kept - 1), $named[$kept - 1]);
        $this->assertSame(sprintf('and %d more', $recorded - $kept), $named[$kept]);
    }

    public function testAListWithinTheCapCarriesNoMarker(): void
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::UPDATED, 'key "weight"');

        $this->assertSame(['key "weight"'], $report->getNamed(ApplyOutcome::UPDATED));
    }

    public function testARecordedConflictMakesTheReportRefuse(): void
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::CONFLICT, 'key "weight" is disabled and assigned');

        $this->assertTrue($report->hasConflicts());
        $this->assertSame(['key "weight" is disabled and assigned'], $report->getNamed(ApplyOutcome::CONFLICT));
    }
}
