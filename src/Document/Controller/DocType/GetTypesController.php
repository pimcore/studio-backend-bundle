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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocTypeType;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ReflectionException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetTypesController extends AbstractApiController
{
    private const string ROUTE = '/documents/doc-types/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly DocTypeServiceInterface $docTypeService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ReflectionException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_documents_doc_type_types',
        methods: ['GET']
    )]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_doc_type_type_list',
        description: 'document_doc_type_type_list_description',
        summary: 'document_doc_type_type_list_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_doc_type_type_list_success_response',
        content: new ItemsJson(DocTypeType::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getTypes(): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->docTypeService->listDocTypeTypes()]);
    }
}
