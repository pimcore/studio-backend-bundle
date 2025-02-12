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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Attribute\Request\ConfigurationRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Grid\MappedParameter\ConfigurationParameter;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\SaveConfigurationServiceInterface;
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
final class SaveConfigurationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SaveConfigurationServiceInterface $gridSaveConfigurationService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/assets/grid/configuration/save',
        name: 'pimcore_studio_api_save_asset_grid_configuration',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Post(
        path: self::PREFIX . '/assets/grid/configuration/save',
        operationId: 'asset_save_grid_configuration',
        description: 'asset_save_grid_configuration_description',
        summary: 'asset_save_grid_configuration_description',
        tags: [Tags::AssetGrid->value]
    )]
    #[ConfigurationRequestBody]
    #[IdParameter(type: 'folder', name: 'folderId', required: true)]
    #[SuccessResponse(
        description: 'asset_save_grid_configuration_success_response',
        content: new JsonContent(ref: Configuration::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function saveAssetGridConfiguration(
        #[MapRequestPayload] ConfigurationParameter $saveConfigurationParameter
    ): Response {
        $configuration = $this->gridSaveConfigurationService->saveAssetGridConfiguration(
            $saveConfigurationParameter
        );

        return $this->jsonResponse($configuration);
    }
}
