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
use Pimcore\Bundle\StudioBackendBundle\Authorization\Service\SessionStatusServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Lets the Studio UI ask whether a session exists without provoking a 401 on the login page.
 * Publicly reachable (see SessionAccessPass) and answers 200 either way.
 *
 * @internal
 */
final class SessionController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SessionStatusServiceInterface $sessionStatusService,
    ) {
        parent::__construct($serializer);
    }

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
    public function getSessionStatus(): JsonResponse
    {
        $response = $this->jsonResponse($this->sessionStatusService->getSessionStatus());
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
