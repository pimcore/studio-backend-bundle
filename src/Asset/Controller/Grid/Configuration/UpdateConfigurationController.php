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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attribute\Request\Grid\UpdateConfigurationRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\Grid\UpdateConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\Grid\UpdateConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateConfigurationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly UpdateConfigurationServiceInterface $gridSaveConfigurationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/assets/grid/configuration/update/{configurationId}',
        name: 'pimcore_studio_api_update_asset_grid_configuration',
        methods: ['PUT'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Put(
        path: self::API_PATH . '/assets/grid/configuration/update/{configurationId}',
        operationId: 'asset_update_grid_configuration',
        description: 'asset_update_grid_configuration_description',
        summary: 'asset_update_grid_configuration_description',
        tags: [Tags::AssetGrid->value]
    )]
    #[UpdateConfigurationRequestBody]
    #[IdParameter('configurationId')]
    #[SuccessResponse(
        description: 'asset_update_grid_configuration_success_response'
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateAssetGridConfiguration(
        #[MapRequestPayload] UpdateConfigurationParameter $updateConfigurationParameter,
        int $configurationId
    ): Response {
        $this->gridSaveConfigurationService->updateAssetGridConfigurationById(
            $updateConfigurationParameter,
            $configurationId
        );

        return new Response();
    }
}
