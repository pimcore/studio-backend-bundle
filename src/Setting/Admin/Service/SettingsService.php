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

use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Event\PreResponse\AdminSettingsEvent;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Hydrator\SettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Repository\SettingRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Settings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\UpdateSettings;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SettingsService implements SettingsServiceInterface
{
    public function __construct(
        private SettingRepositoryInterface $adminSettingRepository,
        private SettingsHydratorInterface $adminSettingsHydrator,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getAdminSettings(): Settings
    {
        $config = $this->adminSettingRepository->getConfiguration();
        $settings = $this->adminSettingsHydrator->hydrate($config);

        $this->eventDispatcher->dispatch(
            new AdminSettingsEvent($settings),
            AdminSettingsEvent::EVENT_NAME
        );

        return $settings;
    }

    public function updateAdminSettings(UpdateSettings $updateAdminSettings): void
    {
        $dehydratedData = $this->adminSettingsHydrator->dehydrate($updateAdminSettings);
        $this->adminSettingRepository->saveConfiguration($dehydratedData);
    }
}
