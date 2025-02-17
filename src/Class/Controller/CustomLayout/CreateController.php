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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\CustomLayout;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Request\CustomLayoutNewRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\CustomLayoutNewParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\CustomLayout\CustomLayout;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
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
final class CreateController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService

    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotWriteableException
     */
    #[Route('/class/{customLayoutId}', name: 'pimcore_studio_api_class_custom_layout_create', methods: ['POST'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Post(
        path: self::PREFIX . '/class/{customLayoutId}',
        operationId: 'pimcore_studio_api_class_custom_layout_create',
        description: 'pimcore_studio_api_class_custom_layout_create_description',
        summary: 'pimcore_studio_api_class_custom_layout_create_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[StringParameter(
        name: 'customLayoutId',
        example: 'CarTodo',
        description: 'pimcore_studio_api_class_custom_layout_create_layout_id',
        required: true
    )]
    #[CustomLayoutNewRequestBody]
    #[SuccessResponse(
        description: 'pimcore_studio_api_class_custom_layout_create_success_response',
        content: new JsonContent(ref: CustomLayout::class, type: 'object')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function createCustomLayout(
        string $customLayoutId,
        #[MapRequestPayload] CustomLayoutNewParameters $customLayoutParameters
    ): JsonResponse {
        return $this->jsonResponse(
            $this->customLayoutService->createCustomLayout($customLayoutId, $customLayoutParameters)
        );
    }
}
