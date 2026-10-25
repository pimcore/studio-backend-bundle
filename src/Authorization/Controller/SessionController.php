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

namespace Pimcore\Bundle\StudioBackendBundle\Authorization\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Authorization\Schema\SessionStatus;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\TwoFactor\TwoFactorStep;
use Scheb\TwoFactorBundle\Security\Authentication\Token\TwoFactorTokenInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Lets the Studio UI ask whether a session exists without provoking a 401 on the login page.
 * Publicly reachable (see SessionAccessPass) and
 * answers 200 either way.
 *
 * @internal
 */
final class SessionController extends AbstractApiController
{
    public const string ROLE_STUDIO_USER = 'ROLE_PIMCORE_USER';

    #[Route('/session', name: 'pimcore_studio_api_session', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . '/session',
        operationId: 'session_get_status',
        description: 'session_get_status_description',
        summary: 'session_get_status_summary',
        tags: [Tags::Authorization->name]
    )]
    #[SuccessResponse(
        description: 'session_get_status_success_response',
        content: new JsonContent(ref: SessionStatus::class)
    )]
    public function getSessionStatus(
        TokenStorageInterface $tokenStorage,
        #[Autowire(param: 'scheb_two_factor.google.digits')]
        int $digits
    ): JsonResponse {
        $response = $this->jsonResponse($this->getStatus($tokenStorage, $digits));
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    /**
     * A login that waits for its two-factor code is no Studio user yet. The route is public, so the
     * two-factor listener does not answer it with its 401, and the UI needs the same step it would get there.
     */
    private function getStatus(TokenStorageInterface $tokenStorage, int $digits): SessionStatus
    {
        $token = $tokenStorage->getToken();
        if ($token instanceof TwoFactorTokenInterface) {
            return new SessionStatus(false, true, TwoFactorStep::fromToken($token)->value, $digits);
        }

        return new SessionStatus($this->isGranted(self::ROLE_STUDIO_USER));
    }
}
