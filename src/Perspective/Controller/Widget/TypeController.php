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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Controller\Widget;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Attribute\Response\Content\WidgetTypesJson;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TypeController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/widgets/types';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WidgetServiceInterface $widgetService
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_get_perspectives_widgets_types', methods: ['GET'])]
    #[IsGranted(UserPermissions::WIDGET_EDITOR->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_widget_get_type_collection',
        description: 'perspective_widget_get_type_collection_description',
        summary: 'perspective_widget_get_type_collection_summary',
        tags: [Tags::Perspectives->name]
    )]
    #[SuccessResponse(
        description: 'perspective_widget_get_type_collection_success_response',
        content: new WidgetTypesJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getWidgetTypes(
    ): JsonResponse {

        return $this->jsonResponse($this->widgetService->listWidgetTypes());
    }
}
