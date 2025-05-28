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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Request\UpdateDocumentRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Document\Attribute\Response\Content\OneOfDocumentsJson;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\DocumentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\FieldValidationFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\DataParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Updater\Service\UpdateServiceInterface;
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
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/documents/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly DocumentServiceInterface $documentService,
        private readonly UpdateServiceInterface $updateService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|FieldValidationFailedException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_update_document', methods: ['PUT'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_update_by_id',
        description: 'document_update_by_id_description',
        summary: 'document_update_by_id_summary',
        tags: [Tags::Documents->value]
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT)]
    #[UpdateDocumentRequestBody]
    #[SuccessResponse(
        description: 'document_update_by_id_success_response',
        content: new OneOfDocumentsJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function documentUpdateById(int $id, #[MapRequestPayload] DataParameter $parameter): JsonResponse
    {
        $this->updateService->update(ElementTypes::TYPE_DOCUMENT, $id, $parameter->getData());

        return $this->jsonResponse($this->documentService->getDocument($id));
    }
}
