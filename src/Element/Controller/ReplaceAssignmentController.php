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

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Attribute\Request\ReplaceAssignmentRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\UsageParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementUsageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class ReplaceAssignmentController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementUsageServiceInterface $elementUsageService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|UserNotFoundException|NotFoundException
     */
    #[Route(
        '/elements/usage/replace/{elementType}/{id}',
        name: 'pimcore_studio_api_elements_usage_replace',
        methods: ['Post']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Post(
        path: self::PREFIX . '/elements/usage/replace/{elementType}/{id}',
        operationId: 'element_usage_replace',
        description: 'element_usage_replace_description',
        summary: 'element_usage_replace_summary',
        tags: [Tags::Elements->name]
    )]
    #[IdParameter]
    #[ElementTypeParameter]
    #[ReplaceAssignmentRequestBody]
    #[SuccessResponse(
        description: 'element_usage_replace_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function replaceAssignmentAction(
        int $id,
        string $elementType,
        #[MapQueryString] UsageParameter $usageParameters,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->elementUsageService->getUsages(
                $elementType,
                $id,
                $usageParameters,
            )
        );
    }
}
