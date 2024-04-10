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

namespace Pimcore\Bundle\StudioApiBundle\Security\Voter;

use Pimcore\Bundle\StudioApiBundle\Exception\NoRequestException;
use Pimcore\Bundle\StudioApiBundle\Exception\NotAuthorizedException;
use Pimcore\Bundle\StudioApiBundle\Security\Trait\RequestTrait;
use Pimcore\Bundle\StudioApiBundle\Service\SecurityServiceInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class AuthorizationVoter extends Voter
{
    use RequestTrait;

    private const SUPPORTED_ATTRIBUTE = 'STUDIO_API';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService

    ) {
    }

    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::SUPPORTED_ATTRIBUTE;
    }

    /**
     * @throws NoRequestException
     * @throws NotAuthorizedException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if($attribute !== self::SUPPORTED_ATTRIBUTE) {
            return false;
        }

        $request = $this->getCurrentRequest($this->requestStack);

        $authToken = $this->getAuthToken($request);

        if(!$this->securityService->checkAuthToken($authToken)) {
            throw new NotAuthorizedException(
                401,
                'Full authentication is required.'
            );
        }

        return true;
    }
}
