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

use Pimcore\Bundle\StudioApiBundle\Exception\NoAuthTokenFound;
use Pimcore\Bundle\StudioApiBundle\Exception\NonPublicTranslationException;
use Pimcore\Bundle\StudioApiBundle\Exception\NoRequestException;
use Pimcore\Bundle\StudioApiBundle\Security\Trait\PublicTranslationTrait;
use Pimcore\Bundle\StudioApiBundle\Security\Trait\RequestTrait;
use Pimcore\Bundle\StudioApiBundle\Service\SecurityServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class PublicTokenVoter extends Voter
{
    use RequestTrait;
    use PublicTranslationTrait;

    private const SUPPORTED_ATTRIBUTE = 'PUBLIC_API_PLATFORM';

    private const SUPPORTED_SUBJECTS = ['translation'];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::SUPPORTED_ATTRIBUTE && in_array((string)$subject, self::SUPPORTED_SUBJECTS, true);
    }

    /**
     * @throws NoRequestException
     * @throws NonPublicTranslationException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {

        $request = $this->getCurrentRequest($this->requestStack);

        try {
            $authToken = $this->getAuthToken($request);
        } catch (NoAuthTokenFound) {
            return $this->voteOnRequest($request, $subject);
        }

        if ($this->securityService->checkAuthToken($authToken)) {
            return true;
        }

        return $this->voteOnRequest($request, $subject);
    }

    /**
     * @throws NonPublicTranslationException
     */
    private function voteOnRequest(Request $request, string $subject): bool
    {
        return match ($subject) {
            'translation' => $this->voteOnTranslation($request->getPayload()),
            default => false,
        };
    }
}
