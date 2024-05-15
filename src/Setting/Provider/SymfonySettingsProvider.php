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

use Pimcore\Bundle\StudioBackendBundle\Exception\SettingNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SymfonySettingsRequest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use UnitEnum;

/**
 * @internal
 */
final class SymfonySettingsProvider implements SymfonySettingsProviderInterface
{
    private const BLACKLIST_PATTERN = [
        'firewalls',
        'key',
        'password',
        'secret',
        'token'
    ];

    public function __construct(private readonly ParameterBagInterface $parameters) {
    }

    public function getSettings(SymfonySettingsRequest $settingsRequest): array
    {
        return array_combine(
            $settingsRequest->getSettings(),
            array_map(
                fn($settingsKey) => $this->getSetting($settingsKey),
                $settingsRequest->getSettings()
            )
        );
    }

    /**
     * @throws SettingNotFoundException
     */
    private function getSetting(string $settingsKey): array|bool|float|int|null|string|UnitEnum
    {
        $this->isValidSetting($settingsKey);

        return $this->parameters->get($settingsKey);
    }

    /**
     * @throws SettingNotFoundException
     */
    private function isValidSetting(string $settingsKey): void
    {
        foreach(self::BLACKLIST_PATTERN as $pattern) {
            if (str_contains($settingsKey, $pattern)) {
                throw new SettingNotFoundException($settingsKey);
            }
        }

        if(!$this->parameters->has($settingsKey)) {
            throw new SettingNotFoundException($settingsKey);
        }
    }
}
