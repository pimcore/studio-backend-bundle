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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Asset\Hydrator;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator\CustomSettingsHydrator;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings\FixedCustomSettings;

/**
 * @internal
 */
final class CustomSettingsHydratorTest extends Unit
{
    private CustomSettingsHydrator $hydrator;

    public function _before(): void
    {
        $this->hydrator = new CustomSettingsHydrator();
    }

    public function testHydrateEmpty(): void
    {
        $fixedCustomSettings = new FixedCustomSettings();
        $dynamicCustomSettings = [];

        $this->assertEquals(
            $this->hydrator->hydrate([]),
            new CustomSettings($fixedCustomSettings, $dynamicCustomSettings)
        );
    }

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
        ], $hydratedCustomSettings->getFixedCustomSettings()->getEmbeddedMetaData());
        $this->assertTrue($hydratedCustomSettings->getFixedCustomSettings()->isEmbeddedMetaDataExtracted());
        $this->assertEquals([
            'imageDimensionsCalculated' => true,
            'imageWidth' => 932,
            'imageHeight' => 327,
        ], $hydratedCustomSettings->getDynamicCustomSettings());
    }
}
