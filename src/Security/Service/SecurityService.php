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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Service;

use Pimcore\Bundle\GenericDataIndexBundle\Service\Permission\ElementPermissionServiceInterface;
use Pimcore\Bundle\StaticResolverBundle\Lib\Tools\Authentication\AuthenticationResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Model\DataObject;
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

    public function isLoggedIn(): bool
    {
        try {
            $this->getCurrentUser();

            return true;
        } catch (UserNotFoundException) {
            return false;
        }
    }

    /**
     * @throws ForbiddenException
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
            throw new ForbiddenException(
                sprintf('You dont have %s permission', $permission)
            );
        }
    }

    /**
     * @throws ForbiddenException
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

    public function getSpecialDataObjectPermissions(
        DataObject $dataObject,
        UserInterface $user,
        string $permission
    ): array {
        /** @var User $user
         *  Because of isAllowed method in the GDI
         * */
        return $this->elementPermissionService->getSpecialPermissions(
            $dataObject,
            $user,
            $permission
        );
    }
}
