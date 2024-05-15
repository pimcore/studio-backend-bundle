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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Request;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

/**
 * @internal
 */
#[Schema(
    schema: 'SettingsStoreRequest',
    title: 'SettingsStoreRequest',
    description: 'SettingsStoreRequest Scheme for API',
    type: 'object'
)]
final class SettingsStoreRequest
{
    private array $settings;
    public function __construct(
        #[Property(description: 'settings', type: 'array', items: new Items(
            ref: SettingsStoreContent::class
        ))]
        array $settings = []
    ) {
        foreach($settings as $setting) {
            $this->settings[] = new SettingsStoreContent($setting['id'], $setting['scope']);
        }
    }

    public function getSettings(): array
    {
        return $this->settings;
    }
}
