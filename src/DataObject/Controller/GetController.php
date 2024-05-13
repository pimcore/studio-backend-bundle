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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\DataObject;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\MethodNotAllowedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\NotFoundResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnauthorizedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnprocessableContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Error\UnsupportedMediaTypeResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly DataObjectSearchServiceInterface $dataObjectSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/data-objects/{id}', name: 'pimcore_studio_api_get_data_object', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::API_PATH . '/data-objects/{id}',
        operationId: 'getDataObjectById',
        description: 'Get data object by id by path parameter',
        summary: 'Get data object by id',
        security: self::SECURITY_SCHEME,
        tags: [Tags::DataObjects->name]
    )]
    #[IdParameter(type: 'data-object')]
    #[SuccessResponse(
        description: 'Data object response',
        content: new JsonContent(ref: DataObject::class)
    )]
    #[UnauthorizedResponse]
    #[NotFoundResponse]
    #[MethodNotAllowedResponse]
    #[UnsupportedMediaTypeResponse]
    #[UnprocessableContentResponse]
    public function getAssetById(int $id): JsonResponse
    {
        return $this->jsonResponse($this->dataObjectSearchService->getDataObjectById($id));
    }
}
