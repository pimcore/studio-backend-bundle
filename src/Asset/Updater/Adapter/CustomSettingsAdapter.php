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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Updater\Adapter;

use Pimcore\Bundle\StudioBackendBundle\Updater\Adapter\UpdateAdapterInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\CustomSettings;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Model\Asset;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function array_key_exists;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.update_adapter')]
final readonly class CustomSettingsAdapter implements UpdateAdapterInterface
{
    private const INDEX_KEY = 'customSettings';

    public function update(ElementInterface $element, array $data): void
    {
        if (!($element instanceof Asset) || !array_key_exists($this->getIndexKey(), $data)) {
            return;
        }

        $this->setCustomSettings($element, $data[$this->getIndexKey()]);
    }

    private function setCustomSettings(
        Asset $element,
        array $customSettings
    ): void {
        foreach ($customSettings as $customSetting) {
            $key = $customSetting['key'];
            $value = $customSetting['value'];
            $element->setCustomSetting($key, $value);

            if ($key === CustomSettings::IMAGE_THUMBNAIL_TIME->value) {
                $element->removeCustomSetting(CustomSettings::IMAGE_THUMBNAIL_ASSET->value);
            }

            if ($key === CustomSettings::IMAGE_THUMBNAIL_ASSET->value) {
                $element->removeCustomSetting(CustomSettings::IMAGE_THUMBNAIL_TIME->value);
            }
        }
    }

    public function getIndexKey(): string
    {
        return self::INDEX_KEY;
    }

    public function supportedElementTypes(): array
    {
        return [
            ElementTypes::TYPE_ASSET,
        ];
    }
}
