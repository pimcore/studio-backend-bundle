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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Service;

use Exception;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\Settings;
use Pimcore\Bundle\StudioBackendBundle\Setting\Admin\Schema\UpdateSettings;

/**
 * @internal
 */
interface SettingsServiceInterface
{
    /**
     * @throws Exception
     */
    public function getAdminSettings(): Settings;

    /**
     * @throws Exception
     */
    public function updateAdminSettings(UpdateSettings $updateAdminSettings): void;
}
