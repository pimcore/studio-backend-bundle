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

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\ToolResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\AdminResolverInterface;
use Pimcore\SystemSettingsConfig;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use function ini_get;

/**
 * @internal
 */
#[AutoconfigureTag('pimcore.studio_backend.settings_provider')]
final readonly class SystemSettingsProvider implements SettingsProviderInterface
{
    private array $systemSettings;

    public function __construct(
        SystemSettingsConfig $systemSettingsConfig,
        private ToolResolverInterface $toolResolver,
        private AdminResolverInterface $adminResolver
    ) {
        $this->systemSettings = $systemSettingsConfig->getSystemSettingsConfig();
    }

    public function getSettings(): array
    {
        return [
            'requiredLanguages' => $this->systemSettings['general']['required_languages'] ??
                $this->systemSettings['general']['valid_languages'],
            'validLanguages' => $this->systemSettings['general']['valid_languages'],
            'availableAdminLanguages' => $this->adminResolver->getLanguages(),
            'debug_admin_translations' => (bool)$this->systemSettings['general']['debug_admin_translations'],
            'main_domain' => $this->systemSettings['general']['domain'],
            'upload_max_filesize' => $this->getUploadMaxFilesize(),
        ];
    }

    private function getUploadMaxFilesize(): int
    {
        $maxUpload = filesize2bytes(ini_get('upload_max_filesize') . 'B');
        $maxPost = filesize2bytes(ini_get('post_max_size') . 'B');
        $uploadMb = min($maxUpload, $maxPost) ?: $maxUpload;

        return (int)$uploadMb;
    }
}
