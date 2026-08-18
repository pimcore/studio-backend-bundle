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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service\Loader;

use Pimcore\Bundle\StudioBackendBundle\Setting\Service\UpdateSettingProviderLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * @internal
 */
final class UpdateSettingsTaggedIteratorAdapter implements UpdateSettingProviderLoaderInterface
{
    public const string UPDATE_SETTINGS_PROVIDER_TAG = 'pimcore.studio_backend.update_settings_provider';

    public function __construct(
        #[AutowireIterator(self::UPDATE_SETTINGS_PROVIDER_TAG)]
        private readonly iterable $taggedSettingProviders,
    ) {
    }

    public function loadUpdateSettingProviders(): array
    {
        return [...$this->taggedSettingProviders];
    }
}
