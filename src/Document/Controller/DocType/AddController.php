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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\DocType;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
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
final class AddController extends AbstractApiController
{
    private const string ROUTE = '/documents/doc-types/add';

    public function __construct(
        private readonly DocTypeServiceInterface $docTypeService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException|InvalidArgumentException
     * @throws NotFoundException|NotWriteableException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_documents_doc_type_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::DOCUMENT_TYPES->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_doc_type_add',
        description: 'document_doc_type_add_description',
        summary: 'document_doc_type_add_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_doc_type_add_success_response',
        content: new JsonContent(ref: DocType::class)
    )]
    #[ReferenceRequestBody(DocTypeAddParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addDocType(
        #[MapRequestPayload] DocTypeAddParameters $parameters
    ): JsonResponse {

        return $this->jsonResponse($this->docTypeService->addDocType($parameters));
    }
}
