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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Voter;

use Doctrine\DBAL\Exception;
use function in_array;
use function is_array;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @internal
 */
final class UserPermissionVoter extends Voter
{
    private const USER_PERMISSIONS_CACHE_KEY = 'studio_backend_user_permissions';

    private array $userPermissions;

    public function __construct(
        private readonly CacheResolverInterface $cacheResolver,
        private readonly DbResolverInterface $dbResolver,
        private readonly SecurityServiceInterface $securityService

    ) {
        $this->getUserPermissions();
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, $this->userPermissions, true);
    }

    /**
     * @throws AccessDeniedException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->securityService->getCurrentUser()->isAllowed($attribute)) {
            throw new AccessDeniedException(sprintf('User does not have permission: %s', $attribute));
        }

        return true;
    }

    /**
     * @throws AccessDeniedException
     */
    private function getUserPermissions(): void
    {
        $userPermissions = $this->cacheResolver->load(self::USER_PERMISSIONS_CACHE_KEY);

        if($userPermissions !== false && is_array($userPermissions)) {
            $this->userPermissions = $userPermissions;

            return;
        }

        $userPermissions = $this->getUserPermissionsFromDataBase();

        $this->cacheResolver->save(
            $userPermissions,
            self::USER_PERMISSIONS_CACHE_KEY
        );

        $this->userPermissions = $userPermissions;
    }

    /**
     * @throws AccessDeniedException
     */
    private function getUserPermissionsFromDataBase(): array
    {
        try {
            $userPermissions = $this->dbResolver->getConnection()->fetchFirstColumn(
                'SELECT `key` FROM users_permission_definitions'
            );
        } catch (Exception) {
            throw new AccessDeniedException('Cannot resolve user permissions');
        }

        return $userPermissions;
    }
}
