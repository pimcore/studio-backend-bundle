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

use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidSettingException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SettingNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Setting\Request\SettingsStoreRequest;

/**
 * @internal
 */
interface SettingsStoreSettingsProviderInterface
{
    /**
     * @throws SettingNotFoundException|InvalidSettingException
     */
    public function getSettings(SettingsStoreRequest $settingsRequest): array;
}
