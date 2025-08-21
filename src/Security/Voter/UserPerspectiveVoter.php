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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\User\Service\UserPerspectiveServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PerspectivePermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

/**
 * @internal
 */
final class UserPerspectiveVoter extends Voter
{
    use RequestTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService,
        private readonly UserPerspectiveServiceInterface $userPerspectiveService
    ) {
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === PerspectivePermissions::VIEW_PERMISSION;
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NoRequestException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $this->securityService->getCurrentUser();
        if ($user->isAllowed(UserPermissions::PERSPECTIVE_EDITOR->value)) {
            return true;
        }

        $this->userPerspectiveService->validatePerspectiveAccess($user, $this->getPerspectiveFromRequest());

        if ($this->securityService->isSessionWritable()) {
            session_write_close();
        }

        return true;
    }

    /**
     * @throws NoRequestException
     */
    private function getPerspectiveFromRequest(): string
    {
        $request = $this->getCurrentRequest($this->requestStack);

        return $request->attributes->getString('perspectiveId');
    }
}
