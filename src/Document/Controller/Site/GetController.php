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

namespace Pimcore\Bundle\StudioBackendBundle\Document\Controller\Site;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Document\Schema\SiteDetail;
use Pimcore\Bundle\StudioBackendBundle\Document\Service\SiteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    private const string ROUTE = '/documents/site/{documentId}';

    public function __construct(
        private readonly SiteServiceInterface $siteService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|NotFoundException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_documents_site_get',
        requirements: ['documentId' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::DOCUMENTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'document_get_site',
        description: 'document_get_site_description',
        summary: 'document_get_site_summary',
        tags: [Tags::Documents->value]
    )]
    #[IdParameter(type: ElementTypes::TYPE_DOCUMENT, name: 'documentId')]
    #[SuccessResponse(
        description: 'document_get_site_success_response',
        content: new JsonContent(ref: SiteDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getSite(int $documentId): JsonResponse
    {
        return $this->jsonResponse($this->siteService->getSite($documentId));
    }
}
