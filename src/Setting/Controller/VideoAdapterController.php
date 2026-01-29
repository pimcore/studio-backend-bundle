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
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\ThumbnailServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class VideoAdapterController extends AbstractApiController
{
    private const string ROUTE = '/settings/adapter/video';

    public function __construct(
        private readonly ThumbnailServiceInterface $thumbnailService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_settings_video_adapter',
        methods: ['GET']
    )]
    #[Get(
        path: self::ROUTE,
        operationId: 'settings_video_adapter_check',
        description: 'settings_video_adapter_check_description',
        summary: 'settings_video_adapter_check_summary',
        tags: [Tags::Settings->value]
    )]
    #[SuccessResponse(
        description: 'settings_video_adapter_check_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function checkVideoAdapter(): JsonResponse
    {
        return $this->jsonResponse($this->thumbnailService->isVideoAdapterValid());
    }
}
