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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\Attribute\Request\SavedSearchRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Schema\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SavedSearchConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SaveConfigurationController extends AbstractApiController
{
    private const string ROUTE = '/search/saved/configuration/save';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SavedSearchConfigurationServiceInterface $saveConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_save_saved_search_configuration',
        methods: ['POST'],
    )]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'saved_search_save_configuration',
        description: 'saved_search_save_configuration_description',
        summary: 'saved_search_save_configuration_summary',
        tags: [Tags::Search->value]
    )]
    #[SavedSearchRequestBody]
    #[SuccessResponse(
        description: 'saved_search_save_configuration_success_response',
        content: new JsonContent(ref: Configuration::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function saveConfiguration(
        #[MapRequestPayload] SavedSearchParameter $parameter
    ): JsonResponse {
        return $this->jsonResponse(
            $this->saveConfigurationService->saveConfiguration($parameter)
        );
    }
}
