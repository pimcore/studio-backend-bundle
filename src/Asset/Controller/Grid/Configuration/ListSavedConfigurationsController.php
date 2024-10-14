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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Grid\Configuration;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Property\GenericCollection;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListSavedConfigurationsController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private ConfigurationServiceInterface $configurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|SearchException
     */
    #[Route(
        '/assets/grid/configurations/{folderId}',
        name: 'pimcore_studio_api_get_asset_saved_grid_configurations',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/grid/configurations/{folderId}',
        operationId: 'asset_get_saved_grid_configurations',
        description: 'asset_get_saved_grid_configurations_description',
        summary: 'asset_get_saved_grid_configurations_summary',
        tags: [Tags::AssetGrid->value]
    )]
    #[IdParameter(
        type: 'folderId',
        name: 'folderId'
    )]
    #[SuccessResponse(
        description: 'asset_get_saved_grid_configurations_success_response',
        content: new CollectionJson(new GenericCollection(Configuration::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAssetSavedGridConfigurations(int $folderId): JsonResponse
    {
        $configurations = $this->configurationService->getGridConfigurationsForFolder($folderId);

        return $this->jsonResponse($configurations);
    }
}
