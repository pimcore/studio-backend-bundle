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

use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AbstractApiException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function fclose;
use function fopen;
use function fwrite;
use function is_string;
use function sprintf;
use function substr;

/**
 * @internal
 */
#[AsCommand(
    name: 'studio-backend-bundle:classificationstore:export',
    description: 'Write the configuration of one classification store as a JSON file.'
)]
final class ExportStoreCommand extends Command
{
    private const string ARGUMENT_STORE = 'store';

    private const string OPTION_OUTPUT = 'output';

    public function __construct(
        private readonly StoreTransferServiceInterface $storeTransferService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                self::ARGUMENT_STORE,
                InputArgument::REQUIRED,
                'The store to export, by identifier or by numeric ID.'
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'o',
                InputOption::VALUE_REQUIRED,
                'The file to write the configuration to. Written to stdout when left out.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            $store = $this->storeTransferService->resolveStore((string) $input->getArgument(self::ARGUMENT_STORE));
            $artifact = $this->storeTransferService->exportStore((int) $store->getId());
        } catch (AbstractApiException $e) {
            // the refusal belongs on stderr, so that a pipeline writing stdout to a file keeps it
            $io->getErrorStyle()->error($e->getMessage());

            return Command::FAILURE;
        }

        $path = $input->getOption(self::OPTION_OUTPUT);

        if (!is_string($path) || $path === '') {
            foreach ($artifact as $piece) {
                // raw, because the artifact is JSON and carries no console markup
                $output->write($piece, false, OutputInterface::OUTPUT_RAW);
            }

            return Command::SUCCESS;
        }

        return $this->writeToFile($artifact, $path, $io);
    }

    /**
     * Each piece is written as it is produced, so that the file is the only place the artifact is
     * ever whole.
     *
     * @param iterable<string> $artifact
     */
    private function writeToFile(iterable $artifact, string $path, SymfonyStyle $io): int
    {
        $handle = fopen($path, 'wb');

        if ($handle === false) {
            $io->getErrorStyle()->error(sprintf('Could not open "%s" for writing.', $path));

            return Command::FAILURE;
        }

        try {
            foreach ($artifact as $piece) {
                if (!$this->writePiece($handle, $piece)) {
                    $io->getErrorStyle()->error(sprintf('Could not write to "%s".', $path));

                    return Command::FAILURE;
                }
            }
        } finally {
            fclose($handle);
        }

        $io->success(sprintf('The store configuration was written to "%s".', $path));

        return Command::SUCCESS;
    }

    /**
     * `fwrite()` reports how many bytes it took, and a destination that is full takes fewer than it
     * was given - or none - without reporting a failure at all. The rest of the piece is written
     * until it is gone, and a call that takes nothing is the failure the count alone is not: left
     * at the first count, the file would be a truncated artifact reported as a successful export.
     *
     * @param resource $handle
     */
    private function writePiece($handle, string $piece): bool
    {
        $remaining = $piece;

        while ($remaining !== '') {
            $written = fwrite($handle, $remaining);

            if ($written === false || $written === 0) {
                return false;
            }

            $remaining = substr($remaining, $written);
        }

        return true;
    }
}
