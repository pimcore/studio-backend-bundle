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

use Pimcore\Config;

/**
 * @internal
 */
final class ConfigSettingsProvider implements SettingsProviderInterface
{
    public function __construct(
        private readonly Config $config
    )
    {

    }

    public function getSettings(): array
    {
        return [
            'asset_tree_paging_limit' => $this->config['assets']['tree_paging_limit'],
            'document_tree_paging_limit' => $this->config['documents']['tree_paging_limit'],
            'object_tree_paging_limit' => $this->config['objects']['tree_paging_limit'],
            'timezone' => $this->config['general']['timezone'],
        ];

    }
}