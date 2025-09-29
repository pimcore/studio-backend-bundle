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

namespace Pimcore\Bundle\StudioBackendBundle\User\Service;

use JsonException;
use Pimcore\Bundle\StaticResolverBundle\Lib\CacheResolverInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ParseException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdatePasswordParameter;
use Pimcore\Bundle\StudioBackendBundle\User\MappedParameter\UpdateUserParameter;
use Pimcore\Bundle\StudioBackendBundle\User\Repository\UserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\KeyBinding;
use Pimcore\Bundle\StudioBackendBundle\User\Schema\UpdateUserProfile;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\CacheKeys;
use Pimcore\Model\User;
use Pimcore\Model\UserInterface;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use function count;
use function sprintf;
use function strlen;

/**
 * @internal
 */
final readonly class UserUpdateService implements UserUpdateServiceInterface
{
    public function __construct(
        private AuthenticationResolverInterface $authenticationResolver,
        private CacheResolverInterface $cacheResolver,
        private SecurityServiceInterface $securityService,
        private UserRepositoryInterface $userRepository,
        private UpdateServiceInterface $updateService,
        private UserPerspectiveServiceInterface $userPerspectiveService,
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function updateUserById(UpdateUserParameter $updateUserParameter, int $userId): void
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
        $user->setTwoFactorAuthentication('required', $updateUserParameter->isTwoFactorAuthenticationRequired());
        $user->setWelcomescreen($updateUserParameter->isWelcomescreen());
        $user->setContentLanguages($updateUserParameter->getContentLanguages());
        $user->setWebsiteTranslationLanguagesEdit($updateUserParameter->getWebsiteTranslationLanguagesEdit());
        $user->setWebsiteTranslationLanguagesView($updateUserParameter->getWebsiteTranslationLanguagesView());
        $user->setKeyBindings(
            $this->getKeyBindingsString($updateUserParameter->getKeyBindings())
        );
        if ($user instanceof User) {
            $user->setDatetimeLocale($updateUserParameter->getDatetimeLocale());
        }

        $user = $this->updateService->updatePermissions($updateUserParameter->getPermissions(), $user);
        $user = $this->updateService->updateRoles($updateUserParameter->getRoles(), $user);
        $user = $this->updateService->updateClasses($updateUserParameter->getClasses(), $user);
        $user = $this->updateService->updateAssetWorkspaces($updateUserParameter->getAssetWorkspaces(), $user);
        $user = $this->updateService->updateDataObjectWorkspaces(
            $updateUserParameter->getDataObjectWorkspaces(),
            $user
        );
        $user = $this->updateService->updateDocumentWorkspaces($updateUserParameter->getDocumentWorkspaces(), $user);
        $user = $this->updateService->updatePerspectives($updateUserParameter->getPerspectives(), $user);

        $this->userRepository->updateUser($user);

        $this->cacheResolver->remove(CacheKeys::USER_PERMISSIONS->value);

    }

    /**
     * {@inheritdoc}
     */
    public function updateUserProfile(User $user, UpdateUserProfile $params): void
    {
        $user->setFirstName($params->getFirstName());
        $user->setLastName($params->getLastName());
        $user->setEmail($params->getEmail());
        $user->setLanguage($params->getLanguage());
        $user->setDatetimeLocale($params->getDatetimeLocale());
        $user->setWelcomescreen($params->isWelcomeScreen());
        $user->setMemorizeTabs($params->isMemorizeTabs());
        $user->setContentLanguages($params->getContentLanguages());
        $user->setKeyBindings(
            $this->getKeyBindingsString($params->getKeyBindings())
        );

        $this->userRepository->updateUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function updatePasswordById(UpdatePasswordParameter $updateParameter, int $userId): void
    {
        $user = $this->userRepository->getUserById($userId);

        if ($user->getName() === 'system') {
            throw new ForbiddenException('System user password cannot be changed');
        }

        if ($user->isAdmin() && !$this->securityService->getCurrentUser()->isAdmin()) {
            throw new ForbiddenException('Only admin can update admin user');
        }

        if ($updateParameter->getPassword() !== $updateParameter->getPasswordConfirmation()) {
            throw new InvalidArgumentException('Passwords do not match');
        }

        if (strlen($updateParameter->getPassword()) < 10) {
            throw new InvalidArgumentException('Passwords have to be at least 10 characters long');
        }

        if ($updateParameter->getOldPassword() !== null) {
            $this->validateOldPassword($updateParameter, $user);
        }

        $passwordHash = $this->authenticationResolver->getPasswordHash(
            $user->getName(),
            $updateParameter->getPassword()
        );

        $user->setPassword($passwordHash);
        $this->userRepository->updateUser($user);
    }

    /**
     * {@inheritdoc}
     */
    public function updateActivePerspective(string $perspectiveId): void
    {
        $user = $this->securityService->getCurrentUser();
        $this->userPerspectiveService->updateActivePerspective($perspectiveId, $user);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function validateOldPassword(UpdatePasswordParameter $parameter, UserInterface $user): void
    {
        $errors = $this->validator->validate($parameter->getOldPassword(), [new UserPassword()]);
        if (count($errors) > 0) {
            throw new InvalidArgumentException('Old password is incorrect');
        }

        /** @var User $user */
        if ($this->authenticationResolver->verifyPassword($user, $parameter->getPassword())) {
            throw new InvalidArgumentException('New password cannot be the same as the old password');
        }
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
