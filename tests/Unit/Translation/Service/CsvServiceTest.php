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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Translation\Service;

use Codeception\Test\Unit;
use Pimcore\Bundle\StaticResolverBundle\Models\Element\ServiceResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\LanguageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Hydrator\CsvSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Repository\TranslationRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\CsvService;
use Pimcore\Bundle\StudioBackendBundle\Translation\Service\TranslatorServiceInterface;
use ReflectionMethod;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Covers the League\Csv-based CSV generation. The relevant behaviour lives in the private
 * buildCsvContent(); it is exercised via reflection because export() only orchestrates
 * heavily-collaborating services around it.
 */
final class CsvServiceTest extends Unit
{
    private CsvService $csvService;

    public function _before(): void
    {
        $this->csvService = new CsvService(
            $this->makeEmpty(CsvSettingsHydratorInterface::class),
            $this->makeEmpty(EventDispatcherInterface::class),
            $this->makeEmpty(LanguageServiceInterface::class),
            $this->makeEmpty(SecurityServiceInterface::class),
            $this->makeEmpty(ServiceResolverInterface::class),
            $this->makeEmpty(TranslationRepositoryInterface::class),
            $this->makeEmpty(TranslatorServiceInterface::class),
        );
    }

    public function testHeaderAndBasicRow(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting', 'en' => 'Hello']],
            ['key', 'en']
        );

        $this->assertSame("key;en\r\ngreeting;Hello\r\n", $csv);
    }

    public function testLineBreaksArePreserved(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting', 'en' => "Hello\nWorld"]],
            ['key', 'en']
        );

        // Line break survives, wrapped in RFC 4180 quoting instead of being stripped to a space.
        $this->assertSame("key;en\r\ngreeting;\"Hello\nWorld\"\r\n", $csv);
    }

    public function testEmbeddedQuotesAreDoubled(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting', 'en' => 'say "hi"']],
            ['key', 'en']
        );

        $this->assertSame("key;en\r\ngreeting;\"say \"\"hi\"\"\"\r\n", $csv);
    }

    public function testDelimiterInValueIsEnclosed(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting', 'en' => 'a;b']],
            ['key', 'en']
        );

        $this->assertSame("key;en\r\ngreeting;\"a;b\"\r\n", $csv);
    }

    public function testMissingColumnRendersEmptyField(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting']],
            ['key', 'en']
        );

        $this->assertSame("key;en\r\ngreeting;\r\n", $csv);
    }

    public function testNonStringValuesAreCast(): void
    {
        $csv = $this->buildCsvContent(
            [['key' => 'greeting', 'creationDate' => 1714780800]],
            ['key', 'creationDate']
        );

        $this->assertSame("key;creationDate\r\ngreeting;1714780800\r\n", $csv);
    }

    private function buildCsvContent(array $translations, array $columns): string
    {
        $method = new ReflectionMethod(CsvService::class, 'buildCsvContent');

        return $method->invoke($this->csvService, $translations, $columns);
    }
}
