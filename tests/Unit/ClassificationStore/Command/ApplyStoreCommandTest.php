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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ClassificationStore\Command;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command\ApplyStoreCommand;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use function file_put_contents;
use function preg_replace;
use function strpos;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * What the command says it did. A dry run decides the same outcomes a real run does and writes none
 * of them, so the line it ends on is the only thing telling a deployment the two apart.
 *
 * @internal
 */
final class ApplyStoreCommandTest extends Unit
{
    private const string ARTIFACT = '{"store":{"identifier":"01STORE","name":"ETIM"}}';

    private string $path = '';

    /**
     * The memory caveat is in the help, in the order an operator should act on it: raise the limit
     * first, which keeps the apply atomic, and only then apply in parts.
     */
    public function testTheHelpStatesTheMemoryCaveatInTheOrderToActOnIt(): void
    {
        $help = preg_replace(
            '/\s+/',
            ' ',
            (new ApplyStoreCommand($this->makeEmpty(StoreTransferServiceInterface::class)))->getHelp()
        );

        $statements = [
            'peak memory grows with the number of entities',
            'roughly 222 MB',
            'under a memory limit of 512M and does not',
            "PHP's stock 128M",
            'Raise memory_limit for the run first',
            'keeps the apply atomic',
            'applied in parts',
            'Each part commits separately',
            'partly applied configuration',
        ];

        $offset = -1;
        foreach ($statements as $statement) {
            $position = strpos($help, $statement);
            $this->assertNotFalse($position, $statement);
            $this->assertGreaterThan($offset, $position, $statement);
            $offset = $position;
        }
    }

    /**
     * A successful dry run reaches the same branch a successful apply does. Reporting it as applied
     * would make a validation-only deployment step look like a completed one.
     */
    public function testASuccessfulDryRunReportsThatNothingWasWritten(): void
    {
        $tester = $this->createTester($this->createReport());
        $status = $tester->execute(['file' => $this->path, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('Dry run', $tester->getDisplay());
        $this->assertStringContainsString('nothing was written', $tester->getDisplay());
        $this->assertStringNotContainsString('was applied', $tester->getDisplay());
    }

    public function testASuccessfulApplyReportsTheConfigurationApplied(): void
    {
        $tester = $this->createTester($this->createReport());
        $status = $tester->execute(['file' => $this->path]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertStringContainsString('was applied', $tester->getDisplay());
        $this->assertStringNotContainsString('Dry run', $tester->getDisplay());
    }

    /**
     * A dry run that found a refusal fails on it, so that the deployment step stops before the real
     * apply rather than after it.
     */
    public function testARefusedDryRunFailsAndNamesTheRefusal(): void
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::CONFLICT, 'group "Measurements": the name is already another entry\'s');

        $tester = $this->createTester($report);
        $status = $tester->execute(['file' => $this->path, '--dry-run' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Measurements', $tester->getDisplay());
    }

    public function testAFileThatCannotBeReadFails(): void
    {
        $tester = $this->createTester($this->createReport());
        $status = $tester->execute(['file' => $this->path . '.missing']);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Could not read', $tester->getDisplay());
    }

    protected function _before(): void
    {
        $this->path = (string) tempnam(sys_get_temp_dir(), 'artifact');
        file_put_contents($this->path, self::ARTIFACT);
    }

    protected function _after(): void
    {
        if ($this->path !== '') {
            @unlink($this->path);
        }
    }

    private function createReport(): ApplyReport
    {
        $report = new ApplyReport();
        $report->record(ApplyOutcome::CREATED, 'group "Measurements"');

        return $report;
    }

    private function createTester(ApplyReport $report): CommandTester
    {
        $service = $this->makeEmpty(StoreTransferServiceInterface::class, [
            'applyArtifact' => $report,
        ]);

        return new CommandTester(new ApplyStoreCommand($service));
    }
}
