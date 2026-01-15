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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Repository;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\Cache\RuntimeCacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Helper\SystemConfigResolverInterface;
use Pimcore\Config\LocationAwareConfigRepository;

/**
 * @internal
 */
final class AdminSettingRepository implements AdminSettingRepositoryInterface
{
    private const string CONFIG_ID = 'admin_system_settings';

    private const string BRANDING = 'branding';

    private const string ASSETS = 'assets';

    private const string SCOPE = 'pimcore_admin_system_settings';

    private const string CACHE_KEY = 'pimcore_admin_system_settings_config';

    private ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    public function __construct(
        private readonly array $adminConfig,
        private readonly RuntimeCacheResolverInterface $cacheResolver,
        private SystemConfigResolverInterface $systemConfigResolver
    ) {
    }

    public function getAdminSystemSettingsConfig(): array
    {
        if ($this->cacheResolver->isRegistered(self::CACHE_KEY)) {
            return $this->cacheResolver->get(self::CACHE_KEY);
        }

        $config = $this->get();
        $this->cacheResolver->set(self::CACHE_KEY, $config);

        return $config;
    }

    public function saveAdminSystemSettingsConfig(array $values): void
    {
        $repository = $this->getRepository();

        $data[self::BRANDING] = [
            'login_screen_invert_colors' => $values['branding']['login_screen_invert_colors'],
            'color_login_screen' => $values['branding']['color_login_screen'],
            'color_admin_interface' => $values['branding']['color_admin_interface'],
            'color_admin_interface_background' => $values['branding']['color_admin_interface_background'],
            'login_screen_custom_image' => str_replace('%', '%%', $values['branding']['login_screen_custom_image']),
        ];

        $data[self::ASSETS] = [
            'hide_edit_image' => $values['assets']['hide_edit_image'],
            'disable_tree_preview' => $values['assets']['disable_tree_preview'],
        ];

        $repository->saveConfig(self::CONFIG_ID, $data, function ($data) {
            return [
                'pimcore_admin' => $data,
            ];
        });
    }

    private function getRepository(): LocationAwareConfigRepository
    {
        if (!$this->locationAwareConfigRepository) {
            $config[self::CONFIG_ID][self::BRANDING] = $this->adminConfig[self::BRANDING];
            $config[self::CONFIG_ID][self::ASSETS] = $this->adminConfig[self::ASSETS];
            $storageConfig = $this->adminConfig['config_location'][self::CONFIG_ID];

            $this->locationAwareConfigRepository = new LocationAwareConfigRepository(
                $config,
                self::SCOPE,
                $storageConfig
            );
        }

        return $this->locationAwareConfigRepository;
    }

    /**
     * @throws Exception
     */
    private function get(): array
    {
        $repository = $this->getRepository();

        $data = $this->systemConfigResolver->getConfigDataByKey($repository, self::CONFIG_ID);
        $loadType = $repository->getReadTargets()[0] ?? null;

        // If the read target is settings-store and no data is found there,
        // load the data from the container config
        if (!$data && $loadType === $repository::LOCATION_SETTINGS_STORE) {
            $data[self::BRANDING] = $this->adminConfig[self::BRANDING];
            $data[self::ASSETS] = $this->adminConfig[self::ASSETS];
            $data['writeable'] = $repository->isWriteable();
        }

        return $data;
    }
}
