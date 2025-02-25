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

namespace Pimcore\Bundle\StudioBackendBundle\Perspective\Controller\Widget;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\DataJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class GetConfigurationController extends AbstractApiController
{
    private const string ROUTE = '/perspectives/widgets/{widgetType}/configuration/{widgetId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WidgetServiceInterface $widgetService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException|InvalidArgumentException|NotFoundException|NotWriteableException
     */
    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_get_perspectives_widgets_config',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::WIDGET_EDIT->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_widget_get_config_by_id',
        description: 'perspective_widget_get_config_by_id_description',
        summary: 'perspective_widget_get_config_by_id_summary',
        tags: [Tags::Perspectives->value]
    )]
    #[StringParameter('widgetId', 'd061699e_da42_4075_b504_c2c93c687819', 'Filter widgets by matching widget Id')]
    #[StringParameter('widgetType', 'element_tree', 'Filter widgets by matching widget type')]
    #[SuccessResponse(
        description: 'perspective_widget_get_config_by_id_success_response',
        content: new DataJson('Data of the widget configuration')
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getWidgetConfig(string $widgetType, string $widgetId): JsonResponse
    {
        return $this->jsonResponse(['data' => $this->widgetService->getWidgetConfigData($widgetType, $widgetId)]);
    }
}
