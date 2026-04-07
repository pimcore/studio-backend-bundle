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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Utility;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageParameters;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\GetPageResponse;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IntParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
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
final class GetPageController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/get-page';

    public function __construct(
        SerializerInterface $serializer,
        private readonly KeyServiceInterface $keyService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_get_page', methods: ['GET'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_get_page',
        description: 'classification_store_configuration_get_page_description',
        summary: 'classification_store_configuration_get_page_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[TextFieldParameter(
        name: 'table',
        description: 'Table to search in (keys or groups)',
        required: true,
        example: 'keys'
    )]
    #[IntParameter(name: 'id', description: 'ID of the item to find', required: true, example: 1)]
    #[IntParameter(name: 'storeId', description: 'ID of the store', required: true, example: 1)]
    #[IntParameter(name: 'pageSize', description: 'Number of items per page', required: true, example: 15)]
    #[TextFieldParameter(name: 'sortKey', description: 'Column to sort by', required: false, example: 'name')]
    #[TextFieldParameter(
        name: 'sortDir',
        description: 'Sort direction (ASC or DESC)',
        required: false,
        example: 'ASC'
    )]
    #[SuccessResponse(
        description: 'classification_store_configuration_get_page_success_response',
        content: new JsonContent(ref: GetPageResponse::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getPage(
        #[MapQueryString] GetPageParameters $parameters = new GetPageParameters()
    ): JsonResponse {
        return $this->jsonResponse(
            $this->keyService->getPage($parameters)
        );
    }
}
