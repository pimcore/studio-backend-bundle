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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command;

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\ApplyReport;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Util\Constant\ApplyOutcome;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function file_get_contents;
use function is_string;
use function sprintf;

/**
 * @internal
 */
#[AsCommand(
    name: 'studio-backend-bundle:classificationstore:apply',
    description: 'Apply a classification store configuration file, creating and updating but never deleting.'
)]
final class ApplyStoreCommand extends Command
{
    private const string ARGUMENT_FILE = 'file';

    private const string OPTION_DRY_RUN = 'dry-run';

    public function __construct(
        private readonly StoreTransferServiceInterface $storeTransferService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::ARGUMENT_FILE,
                InputArgument::REQUIRED,
                'The configuration file to apply.'
            )
            ->addOption(
                self::OPTION_DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Report what would be created, updated, adopted and refused without writing anything.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = (string) $input->getArgument(self::ARGUMENT_FILE);
        $dryRun = (bool) $input->getOption(self::OPTION_DRY_RUN);
        $json = @file_get_contents($path);

        if (!is_string($json)) {
            $io->getErrorStyle()->error(sprintf('Could not read "%s".', $path));

            return Command::FAILURE;
        }

        try {
            $report = $this->storeTransferService->applyArtifact(
                $json,
                $dryRun,
                // printed as it is decided and not kept, so that the output of an ETIM sized store
                // does not become the one part of this command that grows with it
                static fn (string $line) => $output->writeln($line)
            );
        } catch (AbstractApiException $e) {
            $io->getErrorStyle()->error($e->getMessage());

            return Command::FAILURE;
        }

        return $this->reportOutcome($report, $io, $dryRun);
    }

    /**
     * What the run decided, and whether any of it was written. A dry run reaches the same successful
     * outcome as a real one and has persisted nothing, so it may not report the configuration as
     * applied: a deployment reading the line would take a validation pass for a completed one.
     */
    private function reportOutcome(ApplyReport $report, SymfonyStyle $io, bool $dryRun): int
    {
        $counts = [];
        foreach ($report->getCounts() as $outcome => $count) {
            $counts[] = sprintf('%s: %d', $outcome, $count);
        }

        $io->listing($counts);

        if (!$report->hasConflicts()) {
            $io->success(
                $dryRun
                    ? 'Dry run: nothing was written; the configuration can be applied.'
                    : 'The store configuration was applied.'
            );

            return Command::SUCCESS;
        }

        $io->getErrorStyle()->error($report->getNamed(ApplyOutcome::CONFLICT));

        return Command::FAILURE;
    }
}
