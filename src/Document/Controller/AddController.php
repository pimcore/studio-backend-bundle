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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Request\AddDocumentRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementExistsException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
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
    private const string ROUTE = '/documents/add/{parentId}';

    public function __construct(
        private readonly DocumentServiceInterface $documentService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementExistsException|ElementSavingFailedException|ForbiddenException
     * @throws InvalidArgumentException|NotFoundException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_documents_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_add',
        description: 'document_add_description',
        summary: 'document_add_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_add_success_response',
        content: new IdJson('ID of created document element')
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'parentId')]
    #[AddDocumentRequestBody]
    #[DefaultResponses([
        HttpResponseCodes::CONFLICT,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addDocument(
        int $parentId,
        #[MapRequestPayload] DocumentAddParameters $parameters
    ): JsonResponse {

        return $this->jsonResponse(
            [
                'id' => $this->documentService->addDocument($parentId, $parameters),
            ]
        );
    }
}
