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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NonPublicTranslationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PublicTranslationTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use function in_array;
use function is_string;

/**
 * @internal
 */
final class PublicAuthorizationVoter extends Voter
{
    use RequestTrait;
    use PublicTranslationTrait;

    private const string SUPPORTED_ATTRIBUTE = 'PUBLIC_STUDIO_API';

    private const string TRANSLATION_SUBJECT = 'translation';

    private const string RESET_PASSWORD_SUBJECT = 'resetPassword';

    private const string SETTINGS_ADMIN_THUMBNAIL = 'settingsAdminThumbnail';

    private const array SUPPORTED_SUBJECTS = [
        self::TRANSLATION_SUBJECT,
        self::RESET_PASSWORD_SUBJECT,
        self::SETTINGS_ADMIN_THUMBNAIL,
    ];

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly SecurityServiceInterface $securityService
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::SUPPORTED_ATTRIBUTE &&
            in_array($this->getSubjectName($subject), self::SUPPORTED_SUBJECTS, true);
    }

    /**
     * @throws NoRequestException|NonPublicTranslationException
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if ($this->securityService->isLoggedIn()) {
            return true;
        }

        $request = $this->getCurrentRequest($this->requestStack);
        $subjectName = $this->getSubjectName($subject);

        return $this->voteOnRequest($request, $subjectName);
    }

    /**
     * @throws NonPublicTranslationException
     */
    private function voteOnRequest(Request $request, string $subject): bool
    {
        return match ($subject) {
            self::TRANSLATION_SUBJECT => $this->voteOnTranslation($request->getPayload()),
            self::RESET_PASSWORD_SUBJECT, self::SETTINGS_ADMIN_THUMBNAIL => true,
            default => false,
        };
    }

    private function getSubjectName(mixed $subject): string
    {
        if ($subject instanceof MapRequestPayload) {
            return $subject->metadata->getName();
        }

        if (is_string($subject)) {
            return $subject;
        }

        return '';
    }
}
