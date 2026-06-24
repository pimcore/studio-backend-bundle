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

namespace Pimcore\Bundle\StudioBackendBundle\Search\Controller\SavedSearch;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\ConfigurationListItem;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SavedSearchConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListMenuShortcutConfigurationsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/search/saved/configuration/menu-shortcuts';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SavedSearchConfigurationServiceInterface $savedSearchConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_get_saved_search_menu_shortcut_configurations',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'saved_search_get_menu_shortcut_configurations',
        description: 'saved_search_get_menu_shortcut_configurations_description',
        summary: 'saved_search_get_menu_shortcut_configurations_summary',
        tags: [Tags::Search->value]
    )]
    #[SuccessResponse(
        description: 'saved_search_get_menu_shortcut_configurations_success_response',
        content: new CollectionJson(new GenericCollection(ConfigurationListItem::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getSavedSearchMenuShortcutConfigurations(): JsonResponse
    {
        $collection = $this->savedSearchConfigurationService->listMenuShortcutConfigurations();

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
