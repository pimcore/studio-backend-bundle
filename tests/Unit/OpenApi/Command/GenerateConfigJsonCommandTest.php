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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\OpenApi\Command;

use Codeception\Test\Unit;
use Exception;
use League\Flysystem\FilesystemOperator;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\StorageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Command\GenerateConfigJsonCommand;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiExportServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function sys_get_temp_dir;
use function trim;
use function uniqid;
use function unlink;

/**
 * The exported spec is a sync artifact consumed by scripts (service-operations#1713), so the
 * stream contract matters: with --output=- the JSON is the only thing on stdout and every
 * message goes to stderr; in every other mode the messages stay on stdout as they always did.
 *
 * @internal
 */
final class GenerateConfigJsonCommandTest extends Unit
{
    private const array SPEC = [
        'openapi' => '3.1.0',
        'paths' => [
            '/pimcore-studio/api/x' => ['get' => ['operationId' => 'x']],
        ],
    ];

    public function testStdoutModeKeepsTheJsonCleanAndTheDiagnosticsOnStderr(): void
    {
        $tester = $this->createTester();

        $status = $tester->execute(['--output' => '-'], ['capture_stderr_separately' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame(self::SPEC, json_decode(trim($tester->getDisplay()), true));
        $this->assertStringContainsString('zircote/swagger-php', $tester->getErrorOutput());
    }

    public function testFileModeKeepsItsMessagesOnStdout(): void
    {
        $target = sys_get_temp_dir() . '/' . uniqid('openapi-', true) . '.json';
        $tester = $this->createTester();

        $status = $tester->execute(['--output' => $target], ['capture_stderr_separately' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame(self::SPEC, json_decode((string) file_get_contents($target), true));
        $this->assertStringContainsString('written to', $tester->getDisplay());
        $this->assertSame('', $tester->getErrorOutput());
        unlink($target);
    }

    public function testTempStorageModeKeepsItsMessagesOnStdout(): void
    {
        $written = [];
        $tester = $this->createTester(storage: $this->makeEmpty(FilesystemOperator::class, [
            'write' => function (string $path, string $contents) use (&$written): void {
                $written[$path] = $contents;
            },
        ]));

        $status = $tester->execute([], ['capture_stderr_separately' => true]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame(self::SPEC, json_decode($written['studio-backend-openapi.json'] ?? '', true));
        $this->assertStringContainsString('successfully created', $tester->getDisplay());
        $this->assertSame('', $tester->getErrorOutput());
    }

    public function testAnUnwritableTargetIsReportedAsAFailure(): void
    {
        $tester = $this->createTester();

        $status = $tester->execute(['--output' => '/nonexistent-' . uniqid() . '/spec.json']);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Could not write', $tester->getDisplay());
    }

    public function testAFailedExportIsReportedAsAFailure(): void
    {
        $export = $this->makeEmpty(OpenApiExportServiceInterface::class, [
            'exportConfig' => static function (): array {
                throw new Exception('reflection failed');
            },
        ]);
        $tester = $this->createTester($export);

        $status = $tester->execute(['--output' => '-'], ['capture_stderr_separately' => true]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('reflection failed', $tester->getErrorOutput());
        $this->assertSame('', $tester->getDisplay());
    }

    public function testTheOptionsReachTheExport(): void
    {
        $received = null;
        $export = $this->makeEmpty(OpenApiExportServiceInterface::class, [
            'exportConfig' => function (
                ?array $scanPaths = null,
                ?string $locale = null,
                array $scopes = [],
                bool $applyBundleFilter = true,
                bool $sortAllKeys = false,
            ) use (&$received): array {
                $received = [$locale, $scopes, $applyBundleFilter];

                return self::SPEC;
            },
        ]);
        $tester = $this->createTester($export);

        $tester->execute([
            '--output' => '-',
            '--locale' => 'de',
            '--scope' => ['/pimcore-studio/api/bundle/data-hub'],
            '--no-bundle-filter' => true,
        ]);

        $this->assertSame(['de', ['/pimcore-studio/api/bundle/data-hub'], false], $received);
    }

    private function createTester(
        ?object $export = null,
        ?object $storage = null,
    ): CommandTester {
        $export ??= $this->makeEmpty(OpenApiExportServiceInterface::class, [
            'exportConfig' => static fn (): array => self::SPEC,
        ]);
        $storageService = $this->makeEmpty(StorageServiceInterface::class, [
            'getTempStorage' => fn (): FilesystemOperator => $storage
                ?? $this->makeEmpty(FilesystemOperator::class),
        ]);

        return new CommandTester(new GenerateConfigJsonCommand($export, $storageService));
    }
}
