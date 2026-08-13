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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Upload;

use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\FileNamesParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Schema\AssetBatchInfo;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\UploadInfoServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class BatchInfoController extends AbstractApiController
{
    private const string ROUTE = '/assets/exists/{parentId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UploadInfoServiceInterface $uploadInfoService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_asset_upload_batch_info', methods: ['POST'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_upload_batch_info',
        description: 'asset_upload_batch_info_description',
        summary: 'asset_upload_batch_info_summary',
        tags: [Tags::Assets->name]
    )]
    #[IdParameter(type: ElementTypes::TYPE_ASSET, name: 'parentId')]
    #[RequestBody(
        required: true,
        content: new JsonContent(
            required: ['fileNames'],
            properties: [
                new Property(
                    property: 'fileNames',
                    type: 'array',
                    minItems: 1,
                    maxItems: FileNamesParameter::MAX_FILE_NAMES,
                    items: new Items(type: 'string')
                ),
            ],
            type: 'object'
        )
    )]
    #[SuccessResponse(
        description: 'asset_upload_batch_info_success_response',
        content: new ItemsJson(AssetBatchInfo::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNPROCESSABLE_CONTENT,
    ])]
    public function getAssetsExist(
        int $parentId,
        #[MapRequestPayload] FileNamesParameter $parameter
    ): JsonResponse {

        return $this->jsonResponse(
            [
                'items' => $this->uploadInfoService->filesExist(
                    $parentId,
                    $parameter->getFileNames(),
                    $this->securityService->getCurrentUser()
                ),
            ]
        );
    }
}
