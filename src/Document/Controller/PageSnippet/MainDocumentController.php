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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\PageSnippet;

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\ChangeMainDocumentParameters;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\PageSnippetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
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
final class MainDocumentController extends AbstractApiController
{
    private const string ROUTE = '/documents/{id}/page-snippet/change-main-document';

    public function __construct(
        private readonly PageSnippetServiceInterface $pageSnippetService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException
     * @throws InvalidArgumentException|NotFoundException|UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_page_snippet_change_main_document', methods: ['PUT'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_page_snippet_change_main_document',
        description: 'document_page_snippet_change_main_document_description',
        summary: 'document_page_snippet_change_main_document_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_page_snippet_change_main_document_success_response'
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'id')]
    #[ReferenceRequestBody(ChangeMainDocumentParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function changeMainDocument(
        int $id,
        #[MapRequestPayload] ChangeMainDocumentParameters $parameters
    ): Response {
        $this->pageSnippetService->setMainDocument($id, $parameters->getMainDocumentPath());

        return new Response();
    }
}
