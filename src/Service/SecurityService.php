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

namespace Pimcore\Bundle\StudioApiBundle\Service;

use ApiPlatform\Symfony\Security\Exception\AccessDeniedException;
use Pimcore\Bundle\StaticResolverBundle\Models\Tool\TmpStoreResolverInterface;
use Pimcore\Bundle\StudioApiBundle\Dto\Token\Create;
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
        private UserProvider $userProvider,
        private UserPasswordHasherInterface $passwordHasher,
        private TmpStoreResolverInterface $tmpStoreResolver
    ) {
    }

    public function authenticateUser(Create $token): PasswordAuthenticatedUserInterface
    {
        try {
            $user = $this->userProvider->loadUserByIdentifier($token->getUsername());
        } catch (UserNotFoundException) {
            throw new AccessDeniedException('Invalid credentials');
        }

        if(
            !$user instanceof PasswordAuthenticatedUserInterface ||
            !$this->passwordHasher->isPasswordValid($user, $token->getPassword())
        ) {
            throw new AccessDeniedException('Invalid credentials');
        }

        return $user;
    }

    public function isAllowed(string $token): bool
    {
        $entry = $this->tmpStoreResolver->get($token);

        return  $entry !== null && $entry->getId() === $token;
    }
}
