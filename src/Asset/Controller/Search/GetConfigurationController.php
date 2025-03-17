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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller\Search;

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Grid\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Grid\Service\ConfigurationServiceInterface;
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
final class GetConfigurationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ConfigurationServiceInterface $configurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        '/assets/search/configuration/',
        name: 'pimcore_studio_api_get_asset_search_configuration',
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::PREFIX . '/assets/search/configuration/',
        operationId: 'asset_get_search_configuration',
        description: 'asset_get_search_configuration_description',
        summary: 'asset_get_search_configuration_summary',
        tags: [Tags::AssetSearch->value]
    )]
    #[SuccessResponse(
        description: 'asset_get_search_configuration_success_response',
        content: new JsonContent(ref: DetailedConfiguration::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::BAD_REQUEST,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getAssetGridConfiguration(): JsonResponse
    {
        $configuration = $this->configurationService->getAssetSearchConfiguration();

        return $this->jsonResponse($configuration);
    }
}
