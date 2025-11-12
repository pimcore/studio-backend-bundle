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

namespace Pimcore\Bundle\StudioBackendBundle\Gdpr\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Attribute\Request\GdprRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\MappedParameter\GdprStructuredSearchRequest;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GdprManagerServiceInterface $gdprManagerService
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/gdpr/export/start',
        name: 'pimcore_studio_api_gdpr_export_start',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::GDPR->value)]
    #[GET(summary: 'Start background export job', tags: ['GDPR'])]
    #[GET(
        path: self::PREFIX . '/gdpr/export/start',
        operationId: 'start_gdpr_export',
        summary: 'start_gdpr_export_summary',
        description: 'start_gdpr_export_description',
        tags: [Tags::Export->name]
    )]
    #[GdprRequestBody]
    #[SuccessResponse(
        description: 'Job accepted and started',
        content: new JsonContent(
            properties: [
                new Property(property: 'jobId', type: 'string', example: '123e4567...'),
                new Property(property: 'status', type: 'string', example: 'started'),
            ]
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function startExport(
        #[MapRequestPayload] GdprStructuredSearchRequest $request
    ): JsonResponse {
        $jobId = $this->gdprManagerService->startBackgroundExport($request);

        return new JsonResponse(['jobId' => $jobId, 'status' => 'started'], 202);
    }
}
