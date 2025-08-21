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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Voter;

use Doctrine\DBAL\Exception;
use Pimcore\Bundle\StaticResolverBundle\Db\DbResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CacheKeys;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use function in_array;
use function is_array;
use function sprintf;

/**
 * @internal
 */
final class UserPermissionVoter extends Voter
{
    private array $userPermissions;

    public function __construct(
        private readonly CacheResolverInterface $cacheResolver,
        private readonly DbResolverInterface $dbResolver,
        private readonly SecurityServiceInterface $securityService

    ) {
        $this->getUserPermissions();
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, $this->userPermissions, true);
    }

    /**
     * @throws ForbiddenException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$this->securityService->getCurrentUser()->isAllowed($attribute)) {
            throw new ForbiddenException(sprintf('User does not have permission: %s', $attribute));
        }

        if ($this->securityService->isSessionWritable()) {
            session_write_close();
        }

        return true;
    }

    /**
     * @throws ForbiddenException
     */
    private function getUserPermissions(): void
    {
        $userPermissions = $this->cacheResolver->load(CacheKeys::USER_PERMISSIONS->value);

        if ($userPermissions !== false && is_array($userPermissions)) {
            $this->userPermissions = $userPermissions;

            return;
        }

        $userPermissions = $this->getUserPermissionsFromDataBase();

        $this->cacheResolver->save(
            $userPermissions,
            CacheKeys::USER_PERMISSIONS->value
        );

        $this->userPermissions = $userPermissions;
    }

    /**
     * @throws ForbiddenException
     */
    private function getUserPermissionsFromDataBase(): array
    {
        try {
            $userPermissions = $this->dbResolver->getConnection()->fetchFirstColumn(
                'SELECT `key` FROM users_permission_definitions'
            );
        } catch (Exception) {
            throw new ForbiddenException('Cannot resolve user permissions');
        }

        return $userPermissions;
    }
}
