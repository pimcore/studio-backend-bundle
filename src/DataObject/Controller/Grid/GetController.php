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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller\Grid;

use Exception;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Property\GridCollection;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request\DataObjectGridRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\GridParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\GridServiceInterface;
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
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly GridServiceInterface $gridService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     * @throws Exception
     * @throws NotFoundException
     */
    #[Route('/data-objects/grid/{classId}', name: 'pimcore_studio_api_get_data_object_grid', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/data-objects/grid/{classId}',
        operationId: 'data_object_get_grid',
        description: 'data_object_get_grid_description',
        summary: 'data_object_get_grid_summary',
        tags: [Tags::DataObjectsGrid->value]
    )]
    #[DataObjectGridRequestBody]
    #[SuccessResponse(
        description: 'data_object_get_grid_success_response',
        content: new CollectionJson(
            collection: new GridCollection()
        )
    )]
    #[StringParameter(
        name: 'classId',
        example: 'EV',
        description: 'Identifies the class name for which the the grid should be build.',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function getDataObjectGrid(#[MapRequestPayload] GridParameter $gridParameter, string $classId): JsonResponse
    {

        return $this->jsonResponse($this->gridService->getDataObjectGrid($gridParameter, $classId));
    }
}
