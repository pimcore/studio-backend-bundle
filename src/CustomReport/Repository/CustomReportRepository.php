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

namespace Pimcore\Bundle\StudioBackendBundle\CustomReport\Repository;

use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing\Dao;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class CustomReportRepository implements CustomReportRepositoryInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService
    ) {
    }

    public function loadForUser(User $user): array
    {
        /** @var Dao $dao */
        $dao = (new Listing())->getDao();

        return $dao->loadForGivenUser(
            $user
        );
    }

    public function loadForCurrentUser(): array
    {
        /** @var User $currentUser */
        $currentUser = $this->securityService->getCurrentUser();

        return $this->loadForUser(
            $currentUser
        );
    }
}
