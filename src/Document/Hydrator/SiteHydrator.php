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
