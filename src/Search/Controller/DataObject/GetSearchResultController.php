<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Search\Controller\DataObject;

use Exception;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Property\GridCollection;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request\SearchGridRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\SearchGridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class GetSearchResultController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GridSearchServiceInterface $searchService,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route('/search/data-objects/{classId?}', name: 'pimcore_studio_api_get_data_object_search', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/search/data-objects/{classId}',
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
        ?string $classId,
        #[MapRequestPayload] SearchGridParameter $searchGridParameter
    ): JsonResponse {
        return $this->jsonResponse(
            $this->searchService->getDataObjectSearchGrid(
                $searchGridParameter,
                $this->columnConfigurationService->getAvailableDataObjectColumnConfiguration(
                    $classId,
                    1
                ),
                $classId
            )
        );
    }
}
