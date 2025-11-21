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
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * @internal
 */
final class HasOneOfUserPermissionVoter extends Voter
{
    private const string SUPPORTED_ATTRIBUTE = 'HasOneOf';

    public function __construct(
        private readonly SecurityServiceInterface $securityService

    ) {
    }

    /**
     * {@inheritdoc}
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::SUPPORTED_ATTRIBUTE;
    }

    /**
     * @throws ForbiddenException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        foreach ($subject->getPermissionsToCheck() as $permissionToCheck) {
            if ($this->securityService->getCurrentUser()->isAllowed($permissionToCheck)) {
                return true;
            }
        }

        throw new ForbiddenException(
            'Access denied. User does not have one of the following permissions: '
            . implode(
                ', ', $subject->getPermissionsToCheck()
            )
        );
    }
}
