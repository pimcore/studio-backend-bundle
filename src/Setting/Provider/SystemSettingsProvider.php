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

use Pimcore\SystemSettingsConfig;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * @internal
 */
#[AsTaggedItem('pimcore.studio_backend.settings_provider')]
final readonly class SystemSettingsProvider implements SettingsProviderInterface
{
    private array $systemSettings;

    public function __construct(
        SystemSettingsConfig $systemSettingsConfig,
    )
    {
        $this->systemSettings = $systemSettingsConfig->getSystemSettingsConfig();
    }

    public function getSettings(): array
    {
        $requiredLanguages =
            $this->systemSettings['general']['required_languages'] ??
            $this->systemSettings['general']['valid_languages'];


        return [
            'requiredLanguages' => $requiredLanguages,
            'debug_admin_translations' => (bool)$this->systemSettings['general']['debug_admin_translations'],
            'main_domain' => $this->systemSettings['general']['domain'],
        ];
    }
}
