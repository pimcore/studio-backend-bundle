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
        '/assets/grid/available-columns',
        name: 'pimcore_studio_api_get_asset_grid_available_configuration',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets/grid/available-columns',
        operationId: 'asset_get_available_grid_configuration',
        description: 'asset_get_available_grid_configuration_description',
        summary: 'asset_get_available_grid_configuration_summary',
        tags: [Tags::AssetGrid->value]
    )]
    #[SuccessResponse(
        description: 'asset_get_available_grid_configuration_success_response',
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
    public function getAvailableAssetGridConfiguration(): JsonResponse
    {
        $columns = $this->columnConfigurationService->getAvailableAssetColumnConfiguration();

        return $this->jsonResponse([
            'columns' => $columns,
        ]);
    }
}
