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
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\Credentials;
use Pimcore\Bundle\StudioBackendBundle\Exception\AccessDeniedException;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Pimcore\Security\User\UserProvider;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @internal
 */
final readonly class SecurityService implements SecurityServiceInterface
{
    public function __construct(
        private ElementPermissionServiceInterface $elementPermissionService,
        private UserProvider $userProvider,
        private UserPasswordHasherInterface $passwordHasher,
        private TmpStoreResolverInterface $tmpStoreResolver,
        private AuthenticationResolverInterface $authenticationResolver,
    ) {
    }

    /**
     * @throws AccessDeniedException
     */
    public function authenticateUser(Credentials $credentials): PasswordAuthenticatedUserInterface
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($credentials->getUsername());
        } catch (UserNotFoundException) {
            throw new AccessDeniedException();
        }

        if(
            !$user instanceof PasswordAuthenticatedUserInterface ||
            !$this->passwordHasher->isPasswordValid($user, $credentials->getPassword())
        ) {
            throw new AccessDeniedException();
        }

        return $user;
    }

    public function checkAuthToken(string $token): bool
    {
        $entry = $this->tmpStoreResolver->get($token);

        return  $entry !== null && $entry->getId() === $token;
    }

    /**
     * @throws UserNotFoundException
     */
    public function getCurrentUser(): UserInterface
    {
        $pimcoreUser = $this->authenticationResolver->authenticateSession();
        if (!$pimcoreUser instanceof User) {
            throw new UserNotFoundException('No pimcore user found');
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
}
