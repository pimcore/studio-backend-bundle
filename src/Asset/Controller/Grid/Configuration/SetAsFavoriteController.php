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

use OpenApi\Attributes\Post;
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
final class SetAsFavoriteController extends AbstractApiController
{
    private const string ROUTE = '/assets/grid/configuration/set-as-favorite/{configurationId}/{folderId}';

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
        name: 'pimcore_studio_api_asset_set_grid_configuration_as_favorite',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_set_grid_configuration_as_favorite',
        description: 'asset_set_grid_configuration_as_favorite_description',
        summary: 'asset_set_grid_configuration_as_favorite_summary',
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
        description: 'asset_set_grid_configuration_as_favorite_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function setAssetGridConfigurationAsFavorite(
        int $configurationId,
        int $folderId
    ): Response {
        $this->updateConfigurationService->setAssetGridConfigurationAsFavorite($configurationId, $folderId);

        return new Response();
    }
}
