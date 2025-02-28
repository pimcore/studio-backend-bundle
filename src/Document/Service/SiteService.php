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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Service;

use Pimcore\Bundle\StudioBackendBundle\Document\Event\PreResponse\SiteEvent;
use Pimcore\Bundle\StudioBackendBundle\Document\Hydrator\SiteHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\ExcludeMainSiteParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Repository\SiteRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\Site;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderIds;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementFolderPaths;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\UserPermissionTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class SiteService implements SiteServiceInterface
{
    use UserPermissionTrait;

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private SiteHydratorInterface $siteHydrator,
        private SiteRepositoryInterface $siteRepository,
    ) {
    }

    /**
     * @return Site[]
     */
    public function getAvailableSites(ExcludeMainSiteParameter $parameter): array
    {
        $sites = [];
        if ($parameter->getExcludeMainSite() === false) {
            $sites[] = $this->getMainSite();
        }

        $siteList = $this->siteRepository->listSites();
        foreach ($siteList as $siteEntry) {
            $site = $this->siteHydrator->hydrate($siteEntry);

            $this->eventDispatcher->dispatch(new SiteEvent($site), SiteEvent::EVENT_NAME);
            $sites[] = $site;
        }

        return $sites;
    }

    private function getMainSite(): Site
    {
        return new Site(0, [], 'main_site', ElementFolderIds::ROOT->value, ElementFolderPaths::ROOT->value);
    }
}
