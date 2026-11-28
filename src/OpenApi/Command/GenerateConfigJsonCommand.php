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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Command;

use Composer\InstalledVersions;
use Exception;
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiExportServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function sprintf;

/**
 * @internal
 */
#[AsCommand(
    name: 'studio-backend-bundle:generate-openapi-config-json',
    description: 'Generate a JSON file with Open API schema config.'
)]
final class GenerateConfigJsonCommand extends Command
{
    private const OPTION_FILE_NAME = 'file-name';

    private const string OPTION_OUTPUT = 'output';

    private const string OPTION_LOCALE = 'locale';

    private const string OPTION_SCOPE = 'scope';

    private const string OPTION_NO_BUNDLE_FILTER = 'no-bundle-filter';

    private const string OPTION_PRETTY = 'pretty';

    public function __construct(
        private readonly OpenApiExportServiceInterface $openApiExportService,
        private readonly StorageServiceInterface $storageService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_FILE_NAME,
                'f',
                InputOption::VALUE_OPTIONAL,
                'The name of the file to generate in the temp storage (ignored when --output is used).',
                'studio-backend-openapi.json'
            )
            ->addOption(
                self::OPTION_OUTPUT,
                'o',
                InputOption::VALUE_REQUIRED,
                'Write the spec to this file instead of the temp storage; "-" writes it to stdout.'
            )
            ->addOption(
                self::OPTION_LOCALE,
                null,
                InputOption::VALUE_REQUIRED,
                'Translate summary/description values into this locale (e.g. "en"); untranslated keys stay raw.'
            )
            ->addOption(
                self::OPTION_SCOPE,
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Restrict the spec to paths under this prefix (segment-aware, repeatable), '
                . 'e.g. "/pimcore-studio/api/bundle/data-hub".'
            )
            ->addOption(
                self::OPTION_NO_BUNDLE_FILTER,
                null,
                InputOption::VALUE_NONE,
                'Keep the endpoints of optional bundles (SEO, application logger, custom reports) '
                . 'even when those bundles are not active.'
            )
            ->addOption(
                self::OPTION_PRETTY,
                null,
                InputOption::VALUE_NONE,
                'Pretty-print the JSON.'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $target = $input->getOption(self::OPTION_OUTPUT);
        // Only --output=- needs a clean stdout (the JSON itself goes there), so only then do the
        // messages move to stderr; every other mode keeps them on stdout as before.
        $messageOutput = $target === '-' && $output instanceof ConsoleOutputInterface
            ? $output->getErrorOutput()
            : $output;
        $io = new SymfonyStyle($input, $messageOutput);

        try {
            $spec = $this->openApiExportService->exportConfig(
                locale: $input->getOption(self::OPTION_LOCALE),
                scopes: $input->getOption(self::OPTION_SCOPE),
                applyBundleFilter: !$input->getOption(self::OPTION_NO_BUNDLE_FILTER),
            );

            $flags = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
            if ($input->getOption(self::OPTION_PRETTY)) {
                $flags |= JSON_PRETTY_PRINT;
            }
            $json = json_encode($spec, $flags);
        } catch (Exception $e) {
            $io->error(sprintf('Could not generate the OpenAPI spec: %s', $e->getMessage()));

            return Command::FAILURE;
        }

        // The output is only deterministic within one swagger-php version, so record it.
        $io->comment(sprintf(
            'Generated with zircote/swagger-php %s (%d paths)',
            InstalledVersions::getVersion('zircote/swagger-php') ?? 'unknown',
            isset($spec['paths']) ? count($spec['paths']) : 0
        ));

        if ($target === '-') {
            $output->writeln($json, OutputInterface::OUTPUT_RAW);

            return Command::SUCCESS;
        }

        if ($target !== null) {
            if (@file_put_contents($target, $json . "\n") === false) {
                $io->error(sprintf('Could not write to %s', $target));

                return Command::FAILURE;
            }
            $io->success(sprintf('OpenAPI spec written to %s.', $target));

            return Command::SUCCESS;
        }

        return $this->writeToTempStorage($input->getOption(self::OPTION_FILE_NAME), $json, $io);
    }

    private function writeToTempStorage(?string $fileName, string $json, SymfonyStyle $io): int
    {
        if ($this->validateFileName($fileName, $io) === Command::FAILURE) {
            return Command::FAILURE;
        }

        $io->info(
            sprintf('Creating JSON file %s with Open API schema config...', $fileName)
        );

        try {
            $this->storageService->getTempStorage()->write($fileName, $json);
        } catch (Exception|FilesystemException $e) {
            $io->error(
                sprintf('Could not create JSON file: %s', $e->getMessage())
            );

            return Command::FAILURE;
        }

        $io->success(
            sprintf('JSON file %s was successfully created in temp folder.', $fileName)
        );

        return Command::SUCCESS;
    }

    private function validateFileName(?string $fileName, SymfonyStyle $io): ?int
    {
        if (($fileName === '') || ($fileName === null)) {
            $io->error('File name is empty.');

            return Command::FAILURE;
        }

        if (!str_ends_with($fileName, '.json')) {
            $io->error(sprintf('Invalid file name: %s', $fileName));

            return Command::FAILURE;
        }

        return null;
    }
}
