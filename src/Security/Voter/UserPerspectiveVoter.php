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

namespace Pimcore\Bundle\StudioBackendBundle\Security\Voter;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Util\Constant\Perspectives;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\PerspectivePermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use function in_array;

/**
 * @internal
 */
final class UserPerspectiveVoter extends Voter
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
        return $attribute === PerspectivePermissions::VIEW_PERMISSION;
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NoRequestException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $perspectiveId = $this->getPerspectiveFromRequest();
        if ($perspectiveId === Perspectives::DEFAULT_ID->value) {
            return true;
        }
        $user = $this->securityService->getCurrentUser();

        return in_array($perspectiveId, $user->getPerspectives(), true);
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
