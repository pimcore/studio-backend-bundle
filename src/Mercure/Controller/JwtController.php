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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
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
    )]
    #[DefaultResponses]
    public function auth(): Response
    {
        $res = new Response();
        $res->headers->setCookie(
            $this->hubService->createCookie()
        );

        return $res;
    }
}
