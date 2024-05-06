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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings\FixedCustomSettings;

/**
 * @internal
 */
final class CustomSettingsHydrator implements CustomSettingsHydratorInterface
{
    private const METADATA_KEY = 'embeddedMetaData';

    private const METADATA_EXTRACTED_KEY = 'embeddedMetaDataExtracted';

    private const FIXED_CUSTOM_SETTINGS_KEYS = [
        self::METADATA_KEY,
        self::METADATA_EXTRACTED_KEY,
    ];

    public function hydrate(array $customSettings): CustomSettings
    {
        if (empty($customSettings)) {
            return new CustomSettings(new FixedCustomSettings());
        }

        $fixedCustomSettings = $this->getFixedCustomSettings($customSettings);
        $dynamicCustomSettings = $this->getDynamicCustomSettings($customSettings);

        return new CustomSettings(
            fixedCustomSettings: $fixedCustomSettings,
            dynamicCustomSettings: $dynamicCustomSettings
        );
    }

    private function getFixedCustomSettings(
        array $customSettings
    ): FixedCustomSettings {
        $embeddedMetadata = $customSettings[self::METADATA_KEY] ?? [];
        $extracted = $customSettings[self::METADATA_EXTRACTED_KEY] ?? false;

        return new FixedCustomSettings(
            embeddedMetaData: $embeddedMetadata,
            embeddedMetaDataExtracted: $extracted
        );
    }

    private function getDynamicCustomSettings(array $customSettings): array
    {
        foreach (self::FIXED_CUSTOM_SETTINGS_KEYS as $key) {
            if (isset($customSettings[$key])) {
                unset($customSettings[$key]);
            }
        }

        return $customSettings;
    }
}
