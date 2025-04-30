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

namespace Pimcore\Bundle\StudioBackendBundle\DataObject\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Attribute\Request\PathFormatterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\DataObject\MappedParameter\PathFormatterParameter;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Schema\FormatedPath;
use Pimcore\Bundle\StudioBackendBundle\DataObject\Service\PathFormatterServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class PathFormatterController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly PathFormatterServiceInterface $formatterService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        path: '/data-objects/format-path',
        name: 'pimcore_studio_api_get_data_object_format_path',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/data-objects/format-path',
        operationId: 'data_object_format_path',
        description: 'data_object_format_path_description',
        summary: 'data_object_format_path_summary',
        tags: [Tags::DataObjects->value]
    )]
    #[PathFormatterRequestBody]
    #[SuccessResponse(
        description: 'data_object_format_path_success_response',
        content: new CollectionJson(new GenericCollection(FormatedPath::class))
    )]
    #[DefaultResponses([
    ])]
    public function formatPath(#[MapRequestPayload] PathFormatterParameter $pathFormatterParameter): JsonResponse
    {
        $formattedPaths = $this->formatterService->formatPath($pathFormatterParameter);

        return $this->getPaginatedCollection(
            $this->serializer,
            $formattedPaths,
            count($formattedPaths),
        );
    }
}
