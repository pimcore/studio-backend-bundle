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

namespace Pimcore\Bundle\StudioBackendBundle\Version\Controller\Asset;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementProcessingNotCompletedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementStreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UnprocessableContentException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseHeaders;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Version\Service\VersionBinaryServiceInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PdfStreamController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SecurityServiceInterface $securityService,
        private readonly VersionBinaryServiceInterface $versionBinaryService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws ElementProcessingNotCompletedException
     * @throws ElementStreamResourceNotFoundException
     * @throws EnvironmentException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UnprocessableContentException
     */
    #[Route('/versions/{id}/pdf/stream', name: 'pimcore_studio_api_stream_pdf_version', methods: ['GET'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/versions/{id}/pdf/stream',
        operationId: 'version_pdf_stream_by_id',
        description: 'version_pdf_stream_by_id_description',
        summary: 'version_pdf_stream_by_id_summary',
        tags: [Tags::Versions->name]
    )]
    #[IdParameter(type: 'version')]
    #[SuccessResponse(
        description: 'version_pdf_stream_by_id_success_response',
        content: new MediaType('application/pdf'),
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::NOT_COMPLETED,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function streamPdfVersionById(int $id): StreamedResponse
    {
        return $this->versionBinaryService->streamPdfPreview($id, $this->securityService->getCurrentUser());
    }
}
