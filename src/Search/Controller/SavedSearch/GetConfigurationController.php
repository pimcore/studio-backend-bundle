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
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\DetailedConfiguration;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SavedSearchConfigurationServiceInterface;
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
    private const string ROUTE = '/search/saved/configuration/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SavedSearchConfigurationServiceInterface $savedSearchConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_get_saved_search_configuration',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'saved_search_get_configuration',
        description: 'saved_search_get_configuration_description',
        summary: 'saved_search_get_configuration_summary',
        tags: [Tags::Search->value]
    )]
    #[IdParameter(type: 'saved search configuration')]
    #[SuccessResponse(
        description: 'saved_search_get_configuration_success_response',
        content: new JsonContent(ref: DetailedConfiguration::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getSavedSearchConfiguration(int $id): JsonResponse
    {
        return $this->jsonResponse(
            $this->savedSearchConfigurationService->getSavedSearchConfiguration($id)
        );
    }
}
