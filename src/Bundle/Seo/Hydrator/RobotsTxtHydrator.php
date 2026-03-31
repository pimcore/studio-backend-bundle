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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtConfig;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RobotsTxtSiteConfig;

/**
 * @internal
 */
final readonly class RobotsTxtHydrator implements RobotsTxtHydratorInterface
{
    public function hydrateRobotsTxtConfig(array $data, bool $onFileSystem): RobotsTxtConfig
    {
        $siteConfigs = [];
        foreach ($data as $siteId => $content) {
            $siteConfigs[] = new RobotsTxtSiteConfig((int) $siteId, $content);
        }

        return new RobotsTxtConfig($siteConfigs, $onFileSystem);
    }
}
