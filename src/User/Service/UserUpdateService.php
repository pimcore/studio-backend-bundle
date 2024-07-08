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

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Hydrator\UserHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\User as UserSchema;
use Pimcore\Model\UserInterface;
use function strlen;

/**
 * @internal
 */
final class UserUpdateService implements UserUpdateServiceInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly SecurityServiceInterface $securityService,
        private readonly UserHydratorInterface $userHydrator,
        private readonly UpdateServiceInterface $updateService,
        private readonly AuthenticationResolverInterface $authenticationResolver
    ) {
    }

    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException|ParseException
     */
    public function updateUserById(UpdateUserParameter $updateUserParameter, int $userId): UserSchema
    {
        $user = $this->userRepository->getUserById($userId);

        if ($user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin can update admin user');
        }

        if ($this->securityService->getCurrentUser()->isAdmin()) {
            $user->setAdmin($updateUserParameter->isAdmin());
        }

        $user->setEmail($updateUserParameter->getEmail());
        $user->setFirstName($updateUserParameter->getFirstName());
        $user->setLastName($updateUserParameter->getLastName());
        $user->setActive($updateUserParameter->isActive());
        $user->setCloseWarning($updateUserParameter->isCloseWarning());
        $user->setLanguage($updateUserParameter->getLanguage());
        $user->setMemorizeTabs($updateUserParameter->isMemorizeTabs());
        $user->setParentId($updateUserParameter->getParentId());
        $user->setAllowDirtyClose($updateUserParameter->isAllowDirtyClose());
        $user->setTwoFactorAuthentication('required', $updateUserParameter->isTwoFactorAuthenticationEnabled());
        $user->setWelcomescreen($updateUserParameter->isWelcomescreen());
        $user->setContentLanguages($updateUserParameter->getContentLanguages());
        $user->setWebsiteTranslationLanguagesEdit($updateUserParameter->getWebsiteTranslationLanguagesEdit());
        $user->setWebsiteTranslationLanguagesView($updateUserParameter->getWebsiteTranslationLanguagesView());
        $user->setKeyBindings(
            $this->getKeyBindingsString($updateUserParameter->getKeyBindings())
        );

        /** @var UserInterface $user */
        $user = $this->updateService->updatePermissions($updateUserParameter->getPermissions(), $user);
        $user = $this->updateService->updateRoles($updateUserParameter->getRoles(), $user);
        $user = $this->updateService->updateClasses($updateUserParameter->getClasses(), $user);
        $user = $this->updateService->updateAssetWorkspaces($updateUserParameter->getAssetWorkspaces(), $user);
        $user = $this->updateService->updateDataObjectWorkspaces(
            $updateUserParameter->getDataObjectWorkspaces(),
            $user
        );
        $user = $this->updateService->updateDocumentWorkspaces($updateUserParameter->getDocumentWorkspaces(), $user);

        $this->userRepository->updateUser($user);

        return $this->userHydrator->hydrate($user);
    }

    /**
     * @throws NotFoundException|DatabaseException|ForbiddenException
     */
    public function updatePasswordById(UpdatePasswordParameter $updateParameter, int $userId): void
    {
        $this->user = $this->userRepository->getUserById($userId);

        if ($this->user->getName() === 'system') {
            throw new ForbiddenException('System user password cannot be changed');
        }

        if ($this->user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin can update admin user');
        }

        if ($updateParameter->getPassword() !== $updateParameter->getPasswordConfirmation()) {
            throw new InvalidArgumentException('Passwords do not match');
        }

        if (strlen($updateParameter->getPassword()) < 10) {
            throw new InvalidArgumentException('Passwords have to be at least 10 characters long');
        }

        $passwordHash = $this->authenticationResolver->getPasswordHash(
            $this->user->getName(),
            $updateParameter->getPassword()
        );

        $this->user->setPassword($passwordHash);
        $this->userRepository->updateUser($this->user);
    }

    /**
     * @param KeyBinding[] $keyBindings
     *
     * @throws ParseException
     */
    private function getKeyBindingsString(array $keyBindings): string
    {
        $keyBindingString = [];
        foreach ($keyBindings as $keyBinding) {
            $bindings['key'] = $keyBinding->getKey();
            $bindings['action'] = $keyBinding->getAction();
            $bindings['ctrl'] = $keyBinding->getCtrl();
            $bindings['alt'] = $keyBinding->getAlt();
            $bindings['shift'] = $keyBinding->getShift();

            $keyBindingString[] = $bindings;
        }

        try {
            return json_encode($keyBindingString, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ParseException(sprintf('Error parsing key bindings: %s', $e->getMessage()));
        }
    }
}
