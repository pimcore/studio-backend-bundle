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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class JwtController extends AbstractApiController
{
    public function __construct(private readonly HubInterface $clientHub, SerializerInterface $serializer)
    {
        parent::__construct($serializer);
    }

    #[Route('/mercure/auth', name: 'pimcore_studio_mercure_auth', methods: ['POST'])]
    #[Post(
        path: self::API_PATH . '/mercure/auth',
        operationId: 'mercureAuth',
        summary: 'Retrieve JWT token for Mercure hub as cookie',
        tags: [Tags::Mercure->name]
    )]
    #[SuccessResponse(
        description: 'Auth successful',
    )]
    #[DefaultResponses]
    public function auth(): Response
    {
        $res = new Response();
        $res->headers->setCookie(
            $this->createCookie(0)
        );
        return $res;
    }

    private function createCookie(int $lifetime): Cookie
    {
        $urlParts = parse_url($this->clientHub->getPublicUrl());

        return Cookie::create(
            'mercureAuthorization',
            $this->clientHub->getProvider()->getJwt(),
            $lifetime,
            $urlParts['path'] ?? '/',
            $urlParts['host'] ?? '',
            $urlParts['scheme'] === 'https',
            false //TODO: check with FE if this should be true
        );
    }
}
