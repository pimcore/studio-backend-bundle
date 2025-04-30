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

use Pimcore\Bundle\StudioBackendBundle\Setting\Service\SettingProviderLoaderInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

/**
 * @internal
 */
final class TaggedIteratorAdapter implements SettingProviderLoaderInterface
{
    public const SETTINGS_PROVIDER_TAG = 'pimcore.studio_backend.settings_provider';

    public function __construct(
        #[TaggedIterator(self::SETTINGS_PROVIDER_TAG)]
        private readonly iterable $taggedSettingProviders,
    ) {
    }

    public function loadSettingProviders(): array
    {
        return [...$this->taggedSettingProviders];
    }
}
