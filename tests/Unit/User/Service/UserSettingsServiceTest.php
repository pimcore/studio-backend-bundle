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
    private const string PATH = 'Reporting/Dashboards/Dashboards';

    public function testGetWelcomeScreenPathReturnsNullWithoutStoredSettings(): void
    {
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => null,
        ]));

        $this->assertNull($service->getWelcomeScreenPath($this->createUser()));
    }

    public function testGetWelcomeScreenPathReturnsTheStoredPath(): void
    {
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => Expected::once(function (int $user): UserSettings {
                $this->assertSame(7, $user);

                return $this->createSettings(self::PATH);
            }),
        ]));

        $this->assertSame(self::PATH, $service->getWelcomeScreenPath($this->createUser()));
    }

    public function testUpdateWelcomeScreenPathCreatesTheRowOnFirstWrite(): void
    {
        $stored = null;
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => null,
            'update' => Expected::once(static function (UserSettings $settings) use (&$stored): UserSettings {
                $stored = $settings;

                return $settings;
            }),
        ]));

        $service->updateWelcomeScreenPath(self::PATH, $this->createUser());

        $this->assertInstanceOf(UserSettings::class, $stored);
        $this->assertSame(7, $stored->getUser());
        $this->assertSame(self::PATH, $stored->getWelcomeScreenPath());
    }

    public function testUpdateWelcomeScreenPathChangesTheExistingRow(): void
    {
        $existing = $this->createSettings('Old/Path');
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => $existing,
            'update' => Expected::once(function (UserSettings $settings) use ($existing): UserSettings {
                $this->assertSame($existing, $settings);

                return $settings;
            }),
        ]));

        $service->updateWelcomeScreenPath(self::PATH, $this->createUser());

        $this->assertSame(self::PATH, $existing->getWelcomeScreenPath());
    }

    public function testUpdateWelcomeScreenPathWithEmptyStringClearsThePath(): void
    {
        $existing = $this->createSettings(self::PATH);
        $service = new UserSettingsService($this->makeEmpty(UserSettingsRepositoryInterface::class, [
            'getByUser' => $existing,
            'update' => Expected::once($existing),
        ]));

        $service->updateWelcomeScreenPath('', $this->createUser());

        $this->assertNull($existing->getWelcomeScreenPath());
    }

    private function createUser(): UserInterface
    {
        return $this->makeEmpty(UserInterface::class, ['getId' => 7]);
    }

    private function createSettings(?string $welcomeScreenPath): UserSettings
    {
        $settings = new UserSettings();
        $settings->setUser(7);
        $settings->setWelcomeScreenPath($welcomeScreenPath);

        return $settings;
    }
}
