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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Hydrator;

use PHPUnit\Framework\TestCase;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydrator;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSetting\FixedCustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;

/**
 * @internal
 */
final class CustomSettingsHydratorTest extends TestCase
{
    private CustomSettingsHydrator $hydrator;

    protected function setUp(): void
    {
        $this->hydrator = new CustomSettingsHydrator();
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydrator::hydrate
     */
    public function testHydrateEmpty(): void
    {
        $fixedCustomSettings = new FixedCustomSettings();
        $dynamicCustomSettings = [];

        $this->assertEquals(
            $this->hydrator->hydrate([]),
            new CustomSettings($fixedCustomSettings, $dynamicCustomSettings)
        );
    }

    /**
     * @covers \Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydrator::hydrate
     */
    public function testHydrate(): void
    {
        $assetCustomSettings = [
            'embeddedMetaData' => [
                'FileSize' => '6.9 MB',
                'FileType' => 'PNG',
            ],
            'embeddedMetaDataExtracted' => true,
            'imageDimensionsCalculated' => true,
            'imageWidth' => 932,
            'imageHeight' => 327,
        ];

        $hydratedCustomSettings = $this->hydrator->hydrate($assetCustomSettings);

        $this->assertEquals([
            'FileSize' => '6.9 MB',
            'FileType' => 'PNG',
        ], $hydratedCustomSettings->getFixedCustomSettings()->getEmbeddedMetadata());
        $this->assertTrue($hydratedCustomSettings->getFixedCustomSettings()->isEmbeddedMetadataExtracted());
        $this->assertEquals([
            'imageDimensionsCalculated' => true,
            'imageWidth' => 932,
            'imageHeight' => 327,
        ], $hydratedCustomSettings->getDynamicCustomSettings());
    }
}
