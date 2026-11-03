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
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Command\ExportStoreCommand;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Transfer\StoreTransferServiceInterface;
use Pimcore\Model\DataObject\Classificationstore\StoreConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use function stream_wrapper_register;
use function stream_wrapper_unregister;

/**
 * The file the command writes is the artifact, so a write that took fewer bytes than it was given
 * may not be reported as a successful export.
 *
 * @internal
 */
final class ExportStoreCommandTest extends Unit
{
    private const string SCHEME = 'shortwrite';

    private const string PATH = self::SCHEME . '://artifact.json';

    /**
     * @var list<string>
     */
    private const array ARTIFACT = ['{"store":', '{"identifier":"01STORE"}', '}'];

    /**
     * A destination that takes a few bytes at a time still receives the whole artifact: the piece
     * is written until it is gone rather than left at whatever the first call took.
     */
    public function testAPieceTakenInPartsIsWrittenUntilItIsGone(): void
    {
        ShortWriteStream::$acceptPerWrite = 4;

        $tester = $this->createTester();
        $status = $tester->execute(['store' => '1', '--output' => self::PATH]);

        $this->assertSame(Command::SUCCESS, $status);
        $this->assertSame('{"store":{"identifier":"01STORE"}}', ShortWriteStream::$written);
    }

    /**
     * A destination that stops taking anything - a full disk, which `fwrite()` reports as a count
     * of zero rather than as a failure - is the failure, not a shorter file reported as written.
     */
    public function testADestinationThatTakesNothingFailsRatherThanTruncating(): void
    {
        ShortWriteStream::$acceptPerWrite = 4;
        ShortWriteStream::$stopAfterWrites = 1;

        $tester = $this->createTester();
        $status = $tester->execute(['store' => '1', '--output' => self::PATH]);

        $this->assertSame(Command::FAILURE, $status);
        $this->assertStringContainsString('Could not write', $tester->getDisplay());
    }

    protected function _before(): void
    {
        ShortWriteStream::reset();
        stream_wrapper_register(self::SCHEME, ShortWriteStream::class);
    }

    protected function _after(): void
    {
        stream_wrapper_unregister(self::SCHEME);
    }

    private function createTester(): CommandTester
    {
        $store = new StoreConfig();
        $store->setId(1);

        $service = $this->makeEmpty(StoreTransferServiceInterface::class, [
            'resolveStore' => $store,
            'exportStore' => self::ARTIFACT,
        ]);

        return new CommandTester(new ExportStoreCommand($service));
    }
}
