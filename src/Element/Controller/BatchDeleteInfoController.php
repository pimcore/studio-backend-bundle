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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\DeleteInfo;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\BatchDeleteInfoServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ScalarItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
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
final class BatchDeleteInfoController extends AbstractApiController
{
    private const string ROUTE = '/elements/{elementType}/batch-delete-info';

    public function __construct(
        SerializerInterface $serializer,
        private readonly BatchDeleteInfoServiceInterface $batchDeleteInfoService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|InvalidElementTypeException|NotFoundException|SearchException|UserNotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_elements_batch_delete_info',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'element_batch_delete_info',
        description: 'element_batch_delete_info_description',
        summary: 'element_batch_delete_info_summary',
        tags: [Tags::Elements->name]
    )]
    #[ElementTypeParameter]
    #[RequestBody(
        content: new ScalarItemsJson('integer', 'ids')
    )]
    #[SuccessResponse(
        description: 'element_batch_delete_info_success_response',
        content: new JsonContent(ref: DeleteInfo::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function getBatchDeleteInfo(
        string $elementType,
        #[MapRequestPayload] IdsParameter $parameter,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->batchDeleteInfoService->getBatchDeleteInfo(
                $parameter,
                $elementType,
                $this->securityService->getCurrentUser()
            )
        );
    }
}
