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
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Asset\MappedParameter\GridConfigurationIdParameter;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter as IdParameterPath;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter as IdParameterQuery;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class GetConfigurationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ConfigurationServiceInterface $gridConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|SearchException
     */
    #[Route(
        '/assets/grid/configuration/{folderId}',
        name: 'pimcore_studio_api_get_asset_grid_configuration',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/grid/configuration/{folderId}',
        operationId: 'asset_get_grid_configuration_by_folderId',
        description: 'asset_get_grid_configuration_by_folderId_description',
        summary: 'asset_get_grid_configuration_by_folderId_summary',
        tags: [Tags::AssetGrid->value]
    )]
    #[IdParameterPath(name: 'folderId')]
    #[IdParameterQuery(description: 'Configuration ID', namePrefix: 'configuration', required: false)]
    #[SuccessResponse(
        description: 'asset_get_grid_configuration_by_folderId_success_response',
        content: new JsonContent(
            properties: [
                new Property(
                    property: 'columns',
                    type: 'array',
                    items: new Items(ref: ColumnConfiguration::class),
                )],
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getAssetGridConfiguration(
        int $folderId,
        #[MapQueryString] GridConfigurationIdParameter $configurationId = new GridConfigurationIdParameter()
    ): JsonResponse {
        /**
         * @todo: implement usage of $folderId and $configurationId
         * If a configurationId is set, return the saved configuration for the folder.
         * If no configurationId is set, return the default configuration.
         */
        $columns = $this->gridConfigurationService->getDefaultAssetGridConfiguration();

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
