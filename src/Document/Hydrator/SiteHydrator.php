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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Hydrator;

use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Model\Site as SiteModel;

/**
 * @internal
 */
final class SiteHydrator implements SiteHydratorInterface
{
    public function hydrate(SiteModel $siteModel): Site
    {
        return new Site(
            $siteModel->getId(),
            $siteModel->getDomains(),
            $siteModel->getMainDomain(),
            $siteModel->getRootId(),
            $siteModel->getRootPath(),
        );
    }
}
