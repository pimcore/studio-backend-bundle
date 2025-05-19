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
use Pimcore\Bundle\StudioBackendBundle\Document\Service\ExecutionEngine\CloneServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentCloneParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CloneController extends AbstractApiController
{
    private const string ROUTE = '/documents/{id}/clone/{parentId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly CloneServiceInterface $cloneService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_documents_clone', methods: ['POST'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_clone',
        description: 'document_clone_description',
        summary: 'document_clone_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_clone_success_response',
    )]
    #[CreatedResponse(
        description: 'document_clone_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT)]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'parentId')]
    #[ReferenceRequestBody(DocumentCloneParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function documentClone(
        int $id,
        int $parentId,
        #[MapRequestPayload] DocumentCloneParameters $parameters
    ): Response {
        $jobRunId = $this->cloneService->cloneDocuments($id, $parentId, $parameters);
        if ($jobRunId) {

            return $this->jsonResponse(['jobRunId' => $jobRunId], HttpResponseCodes::CREATED->value);
        }

        return new Response();
    }
}
