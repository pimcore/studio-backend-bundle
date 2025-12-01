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
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Gdpr\Service\GdprManagerServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        '/gdpr/export-data/{id}',
        name: 'pimcore_studio_api_gdpr_export_start',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::GDPR->value)]
    #[Get(
        path: self::PREFIX . '/gdpr/export-data/{id}',
        operationId: 'gdpr_export',
        description: 'gdpr_export_description',
        summary: 'gdpr_export_summary',
        tags: [Tags::GDPR->value]
    )]
    #[IdParameter(
        name: 'id',
        required: true,
    )]
    #[TextFieldParameter(
        name: 'providerKey',
        description: 'The key of the single provider to export',
        required: true,
        example: 'pimcore_users'
    )]
    #[SuccessResponse(
        description: 'gdpr_export_success_response',
        content: new MediaType('application/json'),
        headers: [new ContentDisposition('inline')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function startExport(
        int $id,
        #[MapQueryParameter] string $providerKey
    ): Response {
        return $this->gdprManagerService->getExportData($id, $providerKey);
    }
}
