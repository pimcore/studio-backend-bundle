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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Service;

use Pimcore\Bundle\StudioBackendBundle\CustomReport\Hydrator\CustomReportHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\CustomReport\Repository\CustomReportRepositoryInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class CustomReportService implements CustomReportServiceInterface
{
    public function __construct(
        private CustomReportHydratorInterface $customReportHydrator,
        private CustomReportRepositoryInterface $customReportRepository,
    ) {
    }

    public function getCustomReportTree(?User $user = null): array
    {
        return $this->customReportHydrator->hydrateCustomReportTree(
            $user ?
                $this->customReportRepository->loadForUser($user) :
                $this->customReportRepository->loadForCurrentUser()
        );
    }

    public function getCustomReportConfigTree(?User $user = null): array
    {
        return $this->customReportHydrator->hydrateCustomReportConfigTree(
            $user ?
                $this->customReportRepository->loadForUser($user) :
                $this->customReportRepository->loadForCurrentUser()
        );
    }
}
