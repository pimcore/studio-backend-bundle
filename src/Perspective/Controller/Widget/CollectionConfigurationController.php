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
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Schema\WidgetConfig;
use Pimcore\Bundle\StudioBackendBundle\Perspective\Service\WidgetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class CollectionConfigurationController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/perspectives/widgets/configurations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WidgetServiceInterface $widgetService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_get_perspectives_widgets_configurations_list', methods: ['GET'])]
    #[IsGranted(UserPermissions::WIDGET_EDIT->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'perspective_widget_get_config_collection',
        description: 'perspective_widget_get_config_collection_description',
        summary: 'perspective_widget_get_config_collection_summary',
        tags: [Tags::Perspectives->name]
    )]
    #[SuccessResponse(
        description: 'perspective_widget_get_config_collection_success_response',
        content: new CollectionJson(new GenericCollection(WidgetConfig::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getWidgetConfigCollection(
    ): JsonResponse {

        $items = $this->widgetService->listWidgetConfigurations();

        return $this->getPaginatedCollection(
            $this->serializer,
            $items,
            count($items)
        );
    }
}
