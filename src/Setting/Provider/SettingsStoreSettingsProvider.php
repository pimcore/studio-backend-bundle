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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Provider;

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\SettingsStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidSettingException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SettingNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsStoreContent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsStoreRequest;
use Pimcore\Model\Tool\SettingsStore;

/**
 * @internal
 */
final readonly class SettingsStoreSettingsProvider implements SettingsStoreSettingsProviderInterface
{
    public function __construct(private SettingsStoreResolverInterface $settingsStoreResolver)
    {

    }

    /**
     * @throws SettingNotFoundException|InvalidSettingException
     */
    public function getSettings(SettingsStoreRequest $settingsRequest): array
    {
        return array_map(
            fn(SettingsStoreContent $setting) => $this->getSetting($setting),
            $settingsRequest->getSettings()
        );
    }

    /**
     * @throws SettingNotFoundException|InvalidSettingException
     */
    protected function getSetting(SettingsStoreContent $settings): bool|float|int|string|array
    {
        $entry =  $this->isValidSetting($settings);
        try {
            return [
                'id' => $entry->getId(),
                'scope' => $entry->getScope(),
                'data' => $this->convertData($entry->getData(), $entry->getType())
            ];
        } catch (JsonException) {
            throw new InvalidSettingException(
                sprintf('%s with scope %s', $settings->getId(), $settings->getScope())
            );
        }
    }

    private function isValidSetting(SettingsStoreContent $settings): SettingsStore
    {
        $entry = $this->settingsStoreResolver->get($settings->getId(), $settings->getScope());

        if($entry === null) {
            throw new SettingNotFoundException(
                sprintf('%s with scope %s', $settings->getId(), $settings->getScope())
            );
        }

        return $entry;
    }

    /**
     * @throws JsonException
     */
    private function convertData(mixed $data, string $type): bool|float|int|string|array
    {
        return match ($type) {
            'bool' => (bool) $data,
            'string' =>  json_decode($data, true, 512, JSON_THROW_ON_ERROR),
            default => $data
        };
    }
}