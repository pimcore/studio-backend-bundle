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
use Pimcore\Bundle\StudioBackendBundle\Document\MappedParameter\TypeParameter;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocType;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocTypeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListController extends AbstractApiController
{
    private const string ROUTE = '/documents/doc-types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly DocTypeServiceInterface $docTypeService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_list_document_doc_type',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_doc_type_list',
        description: 'document_doc_type_list_description',
        summary: 'document_doc_type_list_summary',
        tags: [Tags::Documents->value]
    )]
    #[TextFieldParameter(name: 'type', description: 'Filter results by docType type', example: 'page')]
    #[SuccessResponse(
        description: 'document_doc_type_list_success_response',
        content: new ItemsJson(DocType::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getDocTypeList(#[MapQueryString] TypeParameter $parameter = new TypeParameter()): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->docTypeService->listDocTypes($parameter->getType())]);
    }
}
