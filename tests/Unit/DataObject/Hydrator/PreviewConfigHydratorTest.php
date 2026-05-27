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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DataObject\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Hydrator\PreviewConfigHydrator;

/**
 * @internal
 */
final class PreviewConfigHydratorTest extends Unit
{
    private PreviewConfigHydrator $hydrator;

    protected function _before(): void
    {
        $this->hydrator = new PreviewConfigHydrator();
    }

    public function testHydratePreviewConfigEntryWithValues(): void
    {
        $rawEntry = [
            'name' => 'locale',
            'label' => 'Locale',
            'values' => ['English' => 'en', 'German' => 'de'],
            'defaultValue' => 'en',
        ];

        $result = $this->hydrator->hydratePreviewConfigEntry($rawEntry);

        $this->assertSame('locale', $result->getName());
        $this->assertSame('Locale', $result->getLabel());
        $this->assertSame([
            ['key' => 'English', 'value' => 'en'],
            ['key' => 'German', 'value' => 'de'],
        ], $result->getValues());
        $this->assertSame('en', $result->getDefaultValue());
    }

    public function testHydratePreviewConfigEntryWithEmptyValues(): void
    {
        $rawEntry = [
            'name' => 'site',
            'label' => 'Site',
            'values' => [],
            'defaultValue' => 'default',
        ];

        $result = $this->hydrator->hydratePreviewConfigEntry($rawEntry);

        $this->assertSame([], $result->getValues());
        $this->assertSame('site', $result->getName());
    }

    public function testHydratePreviewConfigEntryWithMissingValues(): void
    {
        $rawEntry = [
            'name' => 'site',
            'label' => 'Site',
            'defaultValue' => 'default',
        ];

        $result = $this->hydrator->hydratePreviewConfigEntry($rawEntry);

        $this->assertSame([], $result->getValues());
    }

    public function testHydratePreviewConfigEntryWithMultipleValues(): void
    {
        $rawEntry = [
            'name' => 'environment',
            'label' => 'Environment',
            'values' => [
                'Development' => 'dev',
                'Staging' => 'staging',
                'Production' => 'prod',
                'QA' => 'qa',
            ],
            'defaultValue' => 'dev',
        ];

        $result = $this->hydrator->hydratePreviewConfigEntry($rawEntry);

        $expected = [
            ['key' => 'Development', 'value' => 'dev'],
            ['key' => 'Staging', 'value' => 'staging'],
            ['key' => 'Production', 'value' => 'prod'],
            ['key' => 'QA', 'value' => 'qa'],
        ];
        $this->assertSame($expected, $result->getValues());
    }

    public function testHydratePreviewConfigEntryDefaultValueCastToString(): void
    {
        $rawEntry = [
            'name' => 'port',
            'label' => 'Port',
            'values' => [],
            'defaultValue' => 8080,
        ];

        $result = $this->hydrator->hydratePreviewConfigEntry($rawEntry);

        $this->assertSame('8080', $result->getDefaultValue());
    }
}
