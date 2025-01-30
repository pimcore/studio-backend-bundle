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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Header\ContentDisposition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\CustomLayoutServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\StreamResourceNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\MediaType;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\Asset\MimeTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
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
     * @throws EnvironmentException|ForbiddenException|NotFoundException|StreamResourceNotFoundException
     */
    #[Route('/class/custom-layout/export/{customLayoutId}',
        name: 'pimcore_studio_api_class_custom_layout_export',
        methods: ['GET'])]
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
    ])]
    public function exportCustomLayout(string $customLayoutId): JsonResponse
    {
        return $this->customLayoutService->exportCustomLayoutAsJson($customLayoutId);
    }
}
