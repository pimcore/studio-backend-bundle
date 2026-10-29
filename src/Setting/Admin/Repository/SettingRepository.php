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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Config\LocationAwareConfigRepository;
use function sprintf;

/**
 * @internal
 */
final class SettingRepository implements SettingRepositoryInterface
{
    public const string SCOPE = 'studio_backend_admin_settings';

    private ?LocationAwareConfigRepository $locationAwareConfigRepository = null;

    public function __construct(
        private readonly array $adminConfig,
        private readonly array $storageConfig
    ) {
    }

    public function getConfiguration(): array
    {
        [$configData, $dataSource] = $this->loadConfig();
        $configData['isWriteable'] = $this->isRepositoryWritable(
            $dataSource
        );

        return $configData;
    }

    public function saveConfiguration(array $values): void
    {
        $repository = $this->getRepository();

        $repository->saveConfig(Configuration::ADMIN_SETTINGS_NODE, $values, function ($key, $data) {
            return [
                Configuration::ROOT_NODE => [
                        $key => $data,
                ],
            ];
        });
    }

    private function getRepository(): LocationAwareConfigRepository
    {
        if (!$this->locationAwareConfigRepository) {
            $this->locationAwareConfigRepository = new LocationAwareConfigRepository(
                $this->adminConfig,
                self::SCOPE,
                $this->storageConfig
            );
        }

        return $this->locationAwareConfigRepository;
    }

    /**
     * @throws NotFoundException
     * @throws Exception
     */
    private function loadConfig(): array
    {
        [$data, $dataSource] = $this->getRepository()->loadConfigByKey(Configuration::ADMIN_SETTINGS_NODE);
        $loadType = $this->getRepository()->getReadTargets()[0] ?? null;

        // The settings store only holds the admin settings once they have been saved through the UI.
        // Until then the symfony configuration is their only source, so it has to serve as the
        // fallback - otherwise configured branding silently disappears as soon as the read target is
        // switched to the settings store, which is the only way to keep the settings writeable in a
        // production environment. The data source stays unset: the settings are still written to the
        // settings store, so they remain writeable.
        if (!$data && $loadType === LocationAwareConfigRepository::LOCATION_SETTINGS_STORE) {
            $data = $this->adminConfig[Configuration::ADMIN_SETTINGS_NODE] ?? [];
        }

        return [$data, $dataSource];
    }

    /**
     * @throws NotWriteableException
     */
    private function isRepositoryWritable(
        ?string $dataSource = null,
        string $message = 'Could not export the admin settings configuration: %s'
    ): bool {
        try {
            return $this->getRepository()->isWriteable(Configuration::ADMIN_SETTINGS_NODE, $dataSource);
        } catch (Exception $exception) {
            $message = sprintf($message, $exception->getMessage());

            throw new NotWriteableException('Admin settings', $message, $exception);
        }
    }
}
