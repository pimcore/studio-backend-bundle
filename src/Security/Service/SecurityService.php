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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use function sprintf;

/**
 * @internal
 */
final readonly class SecurityService implements SecurityServiceInterface
{
    public function __construct(
        private ElementPermissionServiceInterface $elementPermissionService,
        private AuthenticationResolverInterface $authenticationResolver,
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function getCurrentUser(): UserInterface
    {
        $pimcoreUser = $this->authenticationResolver->authenticateSession();
        if (!$pimcoreUser instanceof User) {
            throw new UserNotFoundException();
        }

        return $pimcoreUser;
    }

    /**
     * @throws AccessDeniedException
     */
    public function hasElementPermission(
        ElementInterface $element,
        UserInterface $user,
        string $permission
    ): void {
        /** @var User $user
         *  Because of isAllowed method in the GDI
         * */
        if (!$this->elementPermissionService->isAllowed(
            $permission,
            $element,
            $user
        )) {
            throw new AccessDeniedException(
                sprintf('You dont have %s permission', $permission)
            );
        }
    }

    /**
     * @throws AccessDeniedException
     *
     * @param array<string> $permissions
     */
    public function hasElementPermissions(
        ElementInterface $element,
        UserInterface $user,
        array $permissions
    ): void {
        foreach ($permissions as $permission) {
            $this->hasElementPermission($element, $user, $permission);
        }
    }
}
