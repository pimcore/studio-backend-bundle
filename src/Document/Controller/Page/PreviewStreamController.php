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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\Page;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\BinaryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidThumbnailException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
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
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PreviewStreamController extends AbstractApiController
{
    private const string ROUTE = '/documents/{id}/page/stream/preview';

    public function __construct(
        private readonly DocumentServiceInterface $documentService,
        private readonly BinaryServiceInterface $binaryService,
        private readonly SecurityServiceInterface $securityService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws EnvironmentException
     * @throws NotFoundException
     * @throws InvalidElementTypeException
     * @throws InvalidThumbnailException
     * @throws UserNotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_stream_page_preview',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_page_stream_preview',
        description: 'document_page_stream_preview_description',
        summary: 'document_page_stream_preview_summary',
        tags: [Tags::Documents->name]
    )]
    #[IdParameter(type: 'image')]
    #[SuccessResponse(
        description: 'document_page_stream_preview_success_response',
        content: [new MediaType('image/jpeg')],
        headers: [new ContentDisposition(HttpResponseHeaders::INLINE_TYPE->value)]
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function streamPagePreview(int $id): StreamedResponse
    {
        $document = $this->documentService->getDocumentElement(
            $this->securityService->getCurrentUser(),
            $id
        );

        return $this->binaryService->streamPagePreviewImage($document);
    }
}
