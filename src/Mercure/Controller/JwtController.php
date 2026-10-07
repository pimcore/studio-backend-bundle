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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Schema\Authorization;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\HubServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class JwtController extends AbstractApiController
{
    public function __construct(
        private readonly HubServiceInterface $hubService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[Route('/mercure/auth', name: 'pimcore_studio_mercure_auth', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . '/mercure/auth',
        operationId: 'mercure_create_cookie',
        description: 'mercure_create_cookie_description',
        summary: 'mercure_create_cookie_summary',
        tags: [Tags::Mercure->name]
    )]
    #[SuccessResponse(
        description: 'mercure_create_cookie_success_response',
        content: new JsonContent(ref: Authorization::class)
    )]
    #[DefaultResponses]
    public function auth(): Response
    {
        // The cookie authorises the subscription; the body tells the client when to come back for
        // a new one. The hub checks authorisation once, at connect time, so a client that lets the
        // cookie lapse reconnects anonymously and loses every private update without any error.
        $response = $this->jsonResponse(
            new Authorization($this->hubService->getCookieLifetime())
        );
        $response->headers->setCookie(
            $this->hubService->createCookie()
        );

        return $response;
    }
}
