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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Service;

use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Hydrator\SettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository\SettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Settings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\UpdateSettings;

/**
 * @internal
 */
final readonly class SettingsService implements SettingsServiceInterface
{
    public function __construct(
        private SettingRepositoryInterface $adminSettingRepository,
        private SettingsHydratorInterface $adminSettingsHydrator,
    ) {
    }

    public function getAdminSettings(): Settings
    {
        $config = $this->adminSettingRepository->getConfiguration();

        return $this->adminSettingsHydrator->hydrate($config);
    }

    public function updateAdminSettings(UpdateSettings $updateAdminSettings): void
    {
        $dehydratedData = $this->adminSettingsHydrator->dehydrate($updateAdminSettings);
        $this->adminSettingRepository->saveConfiguration($dehydratedData);
    }
}
