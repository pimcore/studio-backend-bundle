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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Service;

use Pimcore\Bundle\AdminBundle\System\AdminConfig;
use Pimcore\Bundle\StudioBackendBundle\Setting\Hydrator\AdminSettingsHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Setting\Schema\AdminSettings;

/**
 * @internal
 */
final readonly class AdminSettingsService implements AdminSettingsServiceInterface
{
    public function __construct(
        private AdminConfig $adminConfig,
        private AdminSettingsHydratorInterface $adminSettingsHydrator,
    ) {
    }

    public function getAdminSettings(): AdminSettings
    {
        $settings = $this->adminConfig->getAdminSystemSettingsConfig();

        return $this->adminSettingsHydrator->hydrate($settings);
    }
}
