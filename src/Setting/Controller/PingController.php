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

namespace Pimcore\Bundle\StudioBackendBundle\Setting\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class PingController extends AbstractApiController
{
    private const string ROUTE = '/settings/ping';

    #[Route(self::ROUTE, name: 'pimcore_studio_api_ping_action', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'ping_action',
        description: 'ping_action_description',
        summary: 'ping_action_summary',
        tags: [Tags::Settings->name]
    )]
    #[SuccessResponse(
        description: 'ping_action_success_response'
    )]
    #[DefaultResponses]
    public function ping(): Response
    {
        return new Response();
    }
}
