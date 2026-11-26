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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Class\Service\SelectOptions;

use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\DataProvider;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\ImportDataParser;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;

/**
 * @internal
 */
final class ImportDataParserTest extends Unit
{
    public function testParsesTheConfigurationAndIgnoresTheIdOfTheFile(): void
    {
        $parameters = (new ImportDataParser())->parse(
            '{"id": "OtherStatus", "group": "imported", "adminOnly": true, "useTraits": "",'
            . ' "implementsInterfaces": "App\\\\Model\\\\StatusInterface",'
            . ' "selectOptions": [{"value": "new", "label": "New"}]}'
        );

        $this->assertSame('imported', $parameters->getGroup());
        $this->assertTrue($parameters->isAdminOnly());
        $this->assertSame('', $parameters->getUseTraits());
        $this->assertSame('App\Model\StatusInterface', $parameters->getImplementsInterfaces());
        $this->assertSame([['value' => 'new', 'label' => 'New']], $parameters->getSelectOptions());
    }

    public function testFillsInTheDefaultsOfMissingOptionalFields(): void
    {
        $parameters = (new ImportDataParser())->parse('{"selectOptions": []}');

        $this->assertNull($parameters->getGroup());
        $this->assertFalse($parameters->isAdminOnly());
        $this->assertSame('', $parameters->getUseTraits());
        $this->assertSame('', $parameters->getImplementsInterfaces());
        $this->assertSame([], $parameters->getSelectOptions());
    }

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uploaded file is not valid JSON');

        (new ImportDataParser())->parse('{not json');
    }

    /**
     * @dataProvider notAnExportProvider
     */
    #[DataProvider('notAnExportProvider')]
    public function testRejectsFilesThatAreNotASelectOptionsExport(string $json): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Uploaded file is not a select options export: missing "selectOptions".');

        (new ImportDataParser())->parse($json);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function notAnExportProvider(): array
    {
        return [
            'class definition export' => ['{"id": "EventStatus", "layoutDefinitions": null}'],
            'scalar' => ['"EventStatus"'],
            'empty list' => ['[]'],
        ];
    }

    /**
     * @dataProvider malformedImportProvider
     */
    #[DataProvider('malformedImportProvider')]
    public function testRejectsMalformedValues(string $json, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        (new ImportDataParser())->parse($json);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function malformedImportProvider(): array
    {
        return [
            'adminOnly as string' => [
                '{"adminOnly": "yes", "selectOptions": []}',
                'Invalid value for "adminOnly": expected boolean, got string.',
            ],
            'group as number' => [
                '{"group": 1, "selectOptions": []}',
                'Invalid value for "group": expected string or NULL, got integer.',
            ],
            'selectOptions as object' => [
                '{"selectOptions": {"open": {"value": "open"}}}',
                'Invalid value for "selectOptions": expected a list of objects.',
            ],
            'option as string' => [
                '{"selectOptions": ["open"]}',
                'Invalid value for "selectOptions[0]": expected array, got string.',
            ],
            'option value as number' => [
                '{"selectOptions": [{"value": 1}]}',
                'Invalid value for "selectOptions[0].value": expected string, got integer.',
            ],
        ];
    }
}
