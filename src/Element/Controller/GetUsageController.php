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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\MappedParameter\UsageParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Schema\Subtype;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementUsageServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\SortByParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\SortOrderParameter;
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
final class GetUsageController extends AbstractApiController
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
        '/elements/usage/{elementType}/{id}',
        name: 'pimcore_studio_api_elements_get_usage',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . '/elements/usage/{elementType}/{id}',
        operationId: 'element_get_usage',
        description: 'element_get_usage_description',
        summary: 'element_get_usage_summary',
        tags: [Tags::Elements->name]
    )]
    #[IdParameter]
    #[ElementTypeParameter]
    #[PageParameter]
    #[PageSizeParameter]
    #[SortOrderParameter]
    #[SortByParameter(['id', 'path', 'type'])]
    #[SuccessResponse(
        description: 'element_get_usage_success_response',
        content: new JsonContent(ref: Subtype::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getElementSubtype(
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
