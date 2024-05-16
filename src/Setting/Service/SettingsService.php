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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

/**
 * @internal
 */
final class SettingsService implements SettingsServiceInterface
{
    public function __construct(
        private readonly SettingProviderLoaderInterface $settingProviderLoader
    )
    {
    }

    public function getSettings(): array
    {
        $settings = [];
        foreach($this->settingProviderLoader->loadSettingProviders() as $settingProvider) {
            $settings = [
                ... $settings,
                ... $settingProvider->getSettings()
            ];
        }
       return $settings;
    }
}