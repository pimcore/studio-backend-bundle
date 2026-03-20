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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Grid\Configuration;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\UpdateConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class RemoveFavoriteController extends AbstractApiController
{
    private const string ROUTE = '/assets/grid/configuration/remove-favorite/{configurationId}/{folderId}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly UpdateConfigurationServiceInterface $updateConfigurationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|ForbiddenException|InvalidArgumentException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_asset_remove_grid_configuration_as_favorite',
        methods: ['DELETE'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_remove_grid_configuration_as_favorite',
        description: 'asset_remove_grid_configuration_as_favorite_description',
        summary: 'asset_remove_grid_configuration_as_favorite_summary',
        tags: [Tags::AssetGrid->value]
    )]
    #[IdParameter(
        type: 'configurationId',
        name: 'configurationId'
    )]
    #[IdParameter(
        type: 'folderId',
        name: 'folderId'
    )]
    #[SuccessResponse(
        description: 'asset_remove_grid_configuration_as_favorite_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function removeAssetGridConfigurationAsFavorite(
        int $configurationId,
        int $folderId
    ): Response {
        $this->updateConfigurationService->removeAssetGridConfigurationAsFavorite($configurationId, $folderId);

        return new Response();
    }
}
