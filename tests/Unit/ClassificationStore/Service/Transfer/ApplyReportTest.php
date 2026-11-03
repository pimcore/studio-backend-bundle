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
            ['created', 'updated', 'adopted', 'matchedUnchanged', 'targetOnly', 'conflicts'],
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
     */
    public function testTheNamedListIsCappedWithAnExplicitOverflowMarker(): void
    {
        $report = new ApplyReport();
        $recorded = ApplyReport::NAMED_ENTRY_CAP + 12;

        for ($i = 0; $i < $recorded; $i++) {
            $report->record(ApplyOutcome::CREATED, sprintf('key "k%d"', $i));
        }

        $named = $report->getNamed(ApplyOutcome::CREATED);

        $this->assertSame($recorded, $report->getCount(ApplyOutcome::CREATED));
        $this->assertSame(ApplyReport::NAMED_ENTRY_CAP + 1, count($named));
        $this->assertSame('and 12 more', $named[ApplyReport::NAMED_ENTRY_CAP]);
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
