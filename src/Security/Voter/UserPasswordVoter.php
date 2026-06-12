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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\AccessDeniedException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Pimcore\Helper\ParameterBagHelper;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @internal
 */
final class UserPasswordVoter extends Voter
{
    use RequestTrait;

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService

    ) {
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === UserPermissions::USER_PASSWORD->value;
    }

    /**
     * @throws AccessDeniedException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $userId = $this->getUserIdFromRequest();

        $currentUser = $this->securityService->getCurrentUser();

        if ($userId === $currentUser->getId()) {
            // Allow user to update their own password
            return true;
        }

        return $currentUser->isAllowed(UserPermissions::USER_MANAGEMENT->value);
    }

    private function getUserIdFromRequest(): int
    {
        $request = $this->getCurrentRequest($this->requestStack);

        return ParameterBagHelper::getInt($request->attributes, 'id');
    }
}
