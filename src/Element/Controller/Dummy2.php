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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
final class Dummy2 extends AbstractApiController
{

    #[Route(
        '/test/not-delayed',
        name: 'pimcore_studio_api_test_2',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/test/not_delayed',
        operationId: 'test_not_delayed',
        description: 'test_not_delayed_description',
        summary: 'test_not_delayed_summary',
        tags: [Tags::Elements->name]
    )]
    #[SuccessResponse(
        description: 'test_not_delayed_response_description',
        content: new IdJson('ID of the element')
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function dummyTwo(): JsonResponse
    {
        $sessionId = session_id();
        $startTime = microtime(true);

        if ($_GET['sleep'] ?? false) {
            // Simulate work while keeping the session open
            sleep((int)$_GET['sleep']);
        }

        return new JsonResponse([
            'session_id' => $sessionId,
            'start_time' => $startTime,
            'end_time' => microtime(true),
            'request_id' => uniqid()
        ]);
    }
}
