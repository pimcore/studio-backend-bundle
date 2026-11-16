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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\User\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Entity\User\UserSettings;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserSettingsRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserSettingsService;
use Pimcore\Model\UserInterface;

/**
 * @internal
 */
final class UserSettingsServiceTest extends Unit
{
    private const string DASHBOARD_ID = '019c7555_49f6_712b_af5f_bd0c22b3d1f7';

    public function testGetWelcomeScreenDashboardIdReturnsNullWithoutStoredSettings(): void
    {
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => null,
        ]));

        $this->assertNull($service->getWelcomeScreenDashboardId($this->createUser()));
    }

    public function testGetWelcomeScreenDashboardIdReturnsTheStoredId(): void
    {
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => Expected::once(function (int $user): UserSettings {
                $this->assertSame(7, $user);

                return $this->createSettings(self::DASHBOARD_ID);
            }),
        ]));

        $this->assertSame(self::DASHBOARD_ID, $service->getWelcomeScreenDashboardId($this->createUser()));
    }

    public function testUpdateWelcomeScreenDashboardIdCreatesTheRowOnFirstWrite(): void
    {
        $stored = null;
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => null,
            'update' => Expected::once(static function (UserSettings $settings) use (&$stored): UserSettings {
                $stored = $settings;

                return $settings;
            }),
        ]));

        $service->updateWelcomeScreenDashboardId(self::DASHBOARD_ID, $this->createUser());

        $this->assertInstanceOf(UserSettings::class, $stored);
        $this->assertSame(7, $stored->getUser());
        $this->assertSame(self::DASHBOARD_ID, $stored->getWelcomeScreenDashboardId());
    }

    public function testUpdateWelcomeScreenDashboardIdChangesTheExistingRow(): void
    {
        $existing = $this->createSettings('old_dashboard_id');
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => $existing,
            'update' => Expected::once(function (UserSettings $settings) use ($existing): UserSettings {
                $this->assertSame($existing, $settings);

                return $settings;
            }),
        ]));

        $service->updateWelcomeScreenDashboardId(self::DASHBOARD_ID, $this->createUser());

        $this->assertSame(self::DASHBOARD_ID, $existing->getWelcomeScreenDashboardId());
    }

    public function testUpdateWelcomeScreenDashboardIdWithEmptyStringClearsTheId(): void
    {
        $existing = $this->createSettings(self::DASHBOARD_ID);
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => $existing,
            'update' => Expected::once($existing),
        ]));

        $service->updateWelcomeScreenDashboardId('', $this->createUser());

        $this->assertNull($existing->getWelcomeScreenDashboardId());
    }

    private function createUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 7]);
    }

    private function createSettings(?string $welcomeScreenDashboardId): UserSettings
    {
        $settings = new UserSettings();
        $settings->setUser(7);
        $settings->setWelcomeScreenDashboardId($welcomeScreenDashboardId);

        return $settings;
    }
}
