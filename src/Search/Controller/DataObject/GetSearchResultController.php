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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Controller\DataObject;

use Exception;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Property\GridCollection;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request\SearchGridRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\SearchGridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\ClassIdParameter;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetSearchResultController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GridSearchServiceInterface $searchService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     * @throws InvalidQueryTypeException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws Exception
     */
    #[Route('/search/data-objects', name: 'pimcore_studio_api_get_data_object_search', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/search/data-objects',
        operationId: 'data_object_get_search',
        description: 'data_object_get_search_description',
        summary: 'data_object_get_search_summary',
        tags: [Tags::Search->value]
    )]
    #[StringParameter(
        name: 'classId',
        example: 'EV',
        description: 'Class Id of the data object',
        required: false,
    )]
    #[SearchGridRequestBody]
    #[SuccessResponse(
        description: 'data_object_get_search_success_response',
        content: new CollectionJson(
            collection: new GridCollection()
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function getDataObjectSearchGrid(
        #[MapRequestPayload] SearchGridParameter $searchGridParameter,
        #[MapQueryString] ?ClassIdParameter $classIdParameter,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->searchService->getDataObjectSearchGrid(
                $searchGridParameter,
                $classIdParameter?->getClassId()
            )
        );
    }
}
