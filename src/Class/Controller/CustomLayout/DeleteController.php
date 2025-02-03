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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class DeleteController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotWriteableException|NotFoundException
     */
    #[Route('/class/custom-layout/{customLayoutId}',
        name: 'pimcore_studio_api_class_custom_layout_delete',
        methods: ['DELETE'])
    ]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Delete(
        path: self::PREFIX . '/class/custom-layout/{customLayoutId}',
        operationId: 'pimcore_studio_api_class_custom_layout_delete',
        description: 'pimcore_studio_api_class_custom_layout_delete_description',
        summary: 'pimcore_studio_api_class_custom_layout_delete_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'customLayoutId',
        example: 'CarTodo',
        description: 'pimcore_studio_api_class_custom_layout_delete_layout_id',
        required: true
    )]
    #[SuccessResponse(
        description: 'pimcore_studio_api_class_custom_layout_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        httpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function deleteCustomLayout(string $customLayoutId): Response
    {
        $this->customLayoutService->deleteCustomLayout($customLayoutId);

        return new Response();
    }
}
