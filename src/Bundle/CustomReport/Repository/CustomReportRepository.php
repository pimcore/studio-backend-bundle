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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\CustomReport\Repository;

use Exception;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config;
use Pimcore\Bundle\CustomReportsBundle\Tool\Config\Listing;
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
        return (new Listing())->getDao()->loadForGivenUser(
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
