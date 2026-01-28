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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Provider;

use Pimcore\Bundle\StaticResolverBundle\Lib\ConfigResolver;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\VersionResolverInterface;
use Pimcore\SystemSettingsConfig;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function ini_get;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.settings_provider')]
final readonly class SystemSettingsProvider implements SettingsProviderInterface, UpdateSettingsProviderInterface
{
    private array $systemSettings;

    public function __construct(
        SystemSettingsConfig $systemSettingsConfig,
        private AdminResolverInterface $adminResolver,
        private ToolResolverInterface $toolResolver,
        private VersionResolverInterface $versionResolver,
        private ConfigResolver $configResolver
    ) {
        $this->systemSettings = $systemSettingsConfig->getSystemSettingsConfig();
    }

    public function getSettings(): array
    {
        return [
            'requiredLanguages' => $this->systemSettings['general']['required_languages'] ??
                $this->systemSettings['general']['valid_languages'],
            'validLanguages' => $this->systemSettings['general']['valid_languages'],
            'fallbackLanguages' => $this->systemSettings['general']['fallback_languages'],
            'defaultLanguage' => $this->systemSettings['general']['default_language'] ?? '',
            'language' => $this->systemSettings['general']['language'] ?? '',
            'availableAdminLanguages' => $this->adminResolver->getLanguages(),
            'validLocales' => $this->toolResolver->getSupportedJSLocales(),
            'debug_admin_translations' => (bool)$this->systemSettings['general']['debug_admin_translations'],
            'main_domain' => $this->systemSettings['general']['domain'],
            'upload_max_filesize' => $this->getUploadMaxFilesize(),
            'session_gc_maxlifetime' => $this->getSessionLifeTime(),
            'platform_version' => $this->versionResolver->getPlatformVersion(),
            'version' => $this->versionResolver->getVersion(),
            'environment' => $this->configResolver->getEnvironment(),
        ];
    }

    public function prepareSettingsForUpdate(array $data): array
    {
        $preparedData = [];
        $languages = $data['general']['valid_languages'] ?? [];

        foreach ($languages as $language) {
            $preparedData['general.fallbackLanguages.' . $language] =
                $data['general']['fallback_languages'][$language] ?? '';

            $preparedData['documents.error_pages.localized.' . $language] =
                $data['documents']['error_pages']['localized'][$language] ?? '';
        }

        $preparedData['objects.versions.days'] = $data['objects']['versions']['days'];
        $preparedData['objects.versions.steps'] = $data['objects']['versions']['steps'];
        $preparedData['assets.versions.days'] = $data['assets']['versions']['days'];
        $preparedData['assets.versions.steps'] = $data['assets']['versions']['steps'];
        $preparedData['documents.versions.days'] = $data['documents']['versions']['days'];
        $preparedData['documents.versions.steps'] = $data['documents']['versions']['steps'];
        $preparedData['documents.error_pages.default'] = $data['documents']['error_pages']['default'];
        $preparedData['general.validLanguages'] = implode(',', $languages);
        $preparedData['general.fallbackLanguages'] = $data['general']['fallback_languages'];
        $preparedData['general.requiredLanguages'] = implode($data['general']['required_languages']);
        $preparedData['general.domain'] = $data['general']['domain'];
        $preparedData['general.redirect_to_maindomain'] = $data['general']['redirect_to_maindomain'];
        $preparedData['general.defaultLanguage'] = $data['general']['default_language'];
        $preparedData['general.debug_admin_translations'] = $data['general']['debug_admin_translations'];

        return $preparedData;
    }

    private function getSessionLifeTime(): int
    {
        $lifetime = ini_get('session.gc_maxlifetime');
        if (empty($lifetime)) {
            $lifetime = 120;
        }

        return (int)$lifetime;
    }

    private function getUploadMaxFilesize(): int
    {
        $maxUpload = filesize2bytes(ini_get('upload_max_filesize') . 'B');
        $maxPost = filesize2bytes(ini_get('post_max_size') . 'B');
        $uploadMb = min($maxUpload, $maxPost) ?: $maxUpload;

        return (int)$uploadMb;
    }
}
