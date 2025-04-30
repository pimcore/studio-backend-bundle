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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSetting\FixedCustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\CustomSettings;

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
            embeddedMetadata: $embeddedMetadata,
            embeddedMetadataExtracted: $extracted
        );
    }

    private function getDynamicCustomSettings(array $customSettings): array
    {
        return array_diff_key(
            $customSettings,
            array_flip(self::FIXED_CUSTOM_SETTINGS_KEYS)
        );
    }
}
