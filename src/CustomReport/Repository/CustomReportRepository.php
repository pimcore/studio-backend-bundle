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

use Exception;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing\Dao;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\CustomReportResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\User;

/**
 * @internal
 */
final readonly class CustomReportRepository implements CustomReportRepositoryInterface
{
    public function __construct(
        private SecurityServiceInterface $securityService,
        private CustomReportResolverInterface $customReportResolver
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

    public function loadByName(string $name): Config
    {
        $report = null;
        $exception = null;

        try {
            $report = $this->customReportResolver->getByName($name);
        } catch (Exception $e) {
            $exception = $e;
        }

        if (!$report || $exception) {
            throw new NotFoundException(
                'Report',
                $name,
                'name',
                $exception
            );
        }

        return $report;
    }
}
