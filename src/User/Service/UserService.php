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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use Exception;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Models\User\UserResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\DomainConfigurationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\RateLimitException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SendMailException;
use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Event\UserTreeNodeEvent;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserTreeNodeHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UserListParameter;
use Pimcore\Bundle\StudioBackendBundle\User\RateLimiter\RateLimiterInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\ResetPassword;
use Pimcore\Model\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
final readonly class UserService implements UserServiceInterface
{
    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        private UserResolverInterface $userResolver,
        private MailServiceInterface $mailService,
        private RateLimiterInterface $rateLimiter,
        private LoggerInterface $pimcoreLogger,
        private UserRepositoryInterface $userRepository,
        private UserTreeNodeHydratorInterface $userTreeNodeHydrator,
        private EventDispatcherInterface $eventDispatcher,
        private SecurityServiceInterface $securityService
    ) {
    }

    /**
     * @throws RateLimitException|DomainConfigurationException|SendMailException
     */
    public function resetPassword(ResetPassword $resetPassword): void
    {
        $this->rateLimiter->check();

        $user = $this->userResolver->getByName($resetPassword->getUsername());

        $userChecks = $this->userChecks($user);

        if (!$user || !$userChecks['success']) {
            $this->pimcoreLogger->error('Reset password failed', ['error' => $userChecks['error']]);

            return;
        }

        $token = $this->authenticationResolver->generateTokenByUser($user);

        try {
            $this->mailService->sendResetPasswordMail($user, $token);
        } catch (DomainConfigurationException|SendMailException $exception) {
            $this->pimcoreLogger->error('Error sending password recovery email', ['error' => $exception->getMessage()]);

            throw $exception;
        }

    }

    public function getUserTreeListing(UserListParameter $userListParameter): Collection
    {
        $userListing = $this->userRepository->getUserListingByParentId($userListParameter->getParentId());
        $users = [];

        foreach ($userListing->getUsers() as $user) {
            if ($user->getName() === 'system') {
                continue;
            }

            $userTreeNode = $this->userTreeNodeHydrator->hydrate($user);

            $this->eventDispatcher->dispatch(
                new UserTreeNodeEvent($userTreeNode),
                UserTreeNodeEvent::EVENT_NAME
            );

            $users[] = $userTreeNode;
        }

        return new Collection(
            totalItems: $userListing->getTotalCount(),
            items: $users
        );
    }

    /**
     * @throws NotFoundException|ForbiddenException|DatabaseException
     */
    public function deleteUser(int $userId): void
    {

        $currentUser = $this->securityService->getCurrentUser();
        $userToDelete = $this->userRepository->getUserById($userId);

        if (!$currentUser->isAdmin() && $userToDelete->isAdmin()) {
            throw new ForbiddenException('Only admins can delete other admins');
        }

        try {
            $this->userRepository->deleteUser($userToDelete);
        } catch (Exception $exception) {
            throw new DatabaseException(
                sprintf(
                    'Error deleting user with id %d: %s',
                    $userId,
                    $exception->getMessage()
                )
            );
        }

    }

    /**
     * @return array<string, bool|string>
     */
    private function userChecks(?UserInterface $user): array
    {
        if (!$user) {
            return ['success' => false, 'error' => 'user_unknown'];
        }

        if (!$user->getEmail() || !filter_var($user->getEmail(), FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'user_no_email_address'];
        }

        if (!$user->isActive()) {
            return ['success' => false, 'error' => 'user_inactive'];
        }

        if (!$user->getPassword()) {
            return ['success' => false, 'error' => 'user_no_password'];
        }

        return ['success' => true, 'error' => ''];
    }
}
