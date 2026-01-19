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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\DocumentType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Setting\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class DocumentTypeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/documents/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementServiceInterface $elementConfigService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_documents_type', methods: ['GET'])]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_get_types',
        description: 'document_get_types_description',
        summary: 'document_get_types_summary',
        tags: [Tags::Documents->value]
    )]
    #[SuccessResponse(
        description: 'document_get_types_success_response',
        content: new CollectionJson(new GenericCollection(DocumentType::class))
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getDocumentTypes(): JsonResponse
    {
        $documentTypes = $this->elementConfigService->getDocumentTypes();

        return $this->getPaginatedCollection(
            $this->serializer,
            $documentTypes,
            count($documentTypes)
        );
    }
}
