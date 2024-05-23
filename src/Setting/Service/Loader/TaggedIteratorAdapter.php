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
