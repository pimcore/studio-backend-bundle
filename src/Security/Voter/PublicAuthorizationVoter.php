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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NonPublicTranslationException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NoRequestException;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PublicTranslationTrait;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\RequestTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use function in_array;

/**
 * @internal
 */
final class PublicAuthorizationVoter extends Voter
{
    use RequestTrait;
    use PublicTranslationTrait;

    private const SUPPORTED_ATTRIBUTE = 'PUBLIC_STUDIO_API';

    private const SUPPORTED_SUBJECTS = ['translation'];

    public function __construct(
        private readonly RequestStack $requestStack,
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
            'translation' => $this->voteOnTranslation($request->getPayload()),
            default => false,
        };
    }

    private function getSubjectName(mixed $subject): string
    {
        if ($subject instanceof MapRequestPayload) {
            return $subject->metadata->getName();
        }

        return '';
    }
}
