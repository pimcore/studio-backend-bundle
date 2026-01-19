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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ExportController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly CustomLayoutServiceInterface $customLayoutService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route('/class/custom-layout/export/{customLayoutId}',
        name: 'pimcore_studio_api_class_custom_layout_export',
        methods: ['GET'])]
    #[IsGranted(UserPermissions::DATA_OBJECTS->value)]
    #[Get(
        path: self::PREFIX . '/class/custom-layout/export/{customLayoutId}',
        operationId: 'pimcore_studio_api_class_custom_layout_export',
        description: 'pimcore_studio_api_class_custom_layout_export_description',
        summary: 'pimcore_studio_api_class_custom_layout_export_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[StringParameter(
        name: 'customLayoutId',
        example: 'CarTodo',
        description: 'pimcore_studio_api_class_custom_layout_export_layout_id',
        required: true
    )]
    #[SuccessResponse(
        description: 'pimcore_studio_api_class_custom_layout_export_success_response',
        content: [new MediaType(MimeTypes::JSON->value)],
        headers: [new ContentDisposition(fileName: 'custom_definition_CarToDo_export.json')]
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function exportCustomLayout(string $customLayoutId): Response
    {
        return $this->customLayoutService->exportCustomLayoutAsJson($customLayoutId);
    }
}
