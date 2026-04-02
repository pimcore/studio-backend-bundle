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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\JsonEncodingException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\MultipartFormDataRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ImportController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     * @throws NotWriteableException
     * @throws JsonEncodingException
     * @throws InvalidArgumentException
     */
    #[Route(
        '/class/custom-layout/import/{customLayoutId}',
        name: 'class_custom_layout_import',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/class/custom-layout/import/{customLayoutId}',
        operationId: 'class_custom_layout_import',
        description: 'class_custom_layout_import_description',
        summary: 'class_custom_layout_import_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[StringParameter(
        name: 'customLayoutId',
        example: 'CarTodo',
        description: 'class_custom_layout_import_layout_id',
        required: true
    )]
    #[MultipartFormDataRequestBody(
        [
            new Property(
                property: 'file',
                description: 'Import file to upload',
                type: 'string',
                format: 'binary'
            ),
        ],
        ['file']
    )]
    #[SuccessResponse(
        description: 'class_custom_layout_import_success_response',
        content: new JsonContent(ref: CustomLayout::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::BAD_REQUEST,
    ])]
    public function importCustomLayout(string $customLayoutId, #[MapUploadedFile] UploadedFile $file): JsonResponse
    {
        return $this->jsonResponse(
            $this->customLayoutService->importCustomLayoutActionFromJson(
                $customLayoutId,
                $file->getContent()
            )
        );
    }
}
