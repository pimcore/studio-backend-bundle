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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\ColumnConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ColumnConfigurationServiceInterface;
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
final class GetAvailableColumnsController extends AbstractApiController
{
    private const string ROUTE = '/assets/grid/available-columns';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ColumnConfigurationServiceInterface $columnConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|SearchException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_get_asset_grid_available_configuration',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_get_available_grid_columns',
        description: 'asset_get_available_grid_columns_description',
        summary: 'asset_get_available_grid_columns_summary',
        tags: [Tags::AssetGrid->value]
    )]
    #[SuccessResponse(
        description: 'asset_get_available_grid_columns_success_response',
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
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getAvailableAssetGridColumns(): JsonResponse
    {
        $columns = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
