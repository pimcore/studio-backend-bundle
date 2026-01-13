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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator\AdminSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Repository\AdminSettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\AdminSettings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\UpdateAdminSettings;

/**
 * @internal
 */
final readonly class AdminSettingsService implements AdminSettingsServiceInterface
{
    public function __construct(
        private AdminSettingRepositoryInterface $adminSettingRepository,
        private AdminSettingsHydratorInterface $adminSettingsHydrator,
    ) {
    }

    public function getAdminSettings(): AdminSettings
    {
        $config = $this->adminSettingRepository->getAdminSystemSettingsConfig();

        return $this->adminSettingsHydrator->hydrate($config);
    }

    public function updateAdminSettings(UpdateAdminSettings $updateAdminSettings): void
    {
        $dehydratedData = $this->adminSettingsHydrator->dehydrate($updateAdminSettings);
        $this->adminSettingRepository->saveAdminSystemSettingsConfig($dehydratedData);
    }
}
