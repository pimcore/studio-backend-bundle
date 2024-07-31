<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */


namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Command;

use Exception;
use League\Flysystem\FilesystemException;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

    public function __construct(
        private readonly OpenApiServiceInterface $openApiService,
        private readonly StorageServiceInterface $storageService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                self::OPTION_FILE_NAME,
                'f',
                InputOption::VALUE_OPTIONAL,
                'The name of the file to generate.',
                'studio-backend-openapi.json'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $config = $this->openApiService->getConfig();
        $storage = $this->storageService->getTempStorage();
        $fileName = $input->getOption(self::OPTION_FILE_NAME);
        if ($this->validateFileName($fileName, $io) === Command::FAILURE) {
            return Command::FAILURE;
        }

        $io->info(
            sprintf('Creating JSON file %s with Open API schema config...', $fileName)
        );
        try {
            $storage->write(
                $fileName,
                json_encode($config, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            );
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
