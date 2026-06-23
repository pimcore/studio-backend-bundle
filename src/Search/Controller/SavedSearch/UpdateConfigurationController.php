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

use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\Attribute\Request\SavedSearchRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Search\MappedParameter\SavedSearchParameter;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SavedSearchConfigurationServiceInterface;
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
    private const string ROUTE = '/search/saved/configuration/update/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly SavedSearchConfigurationServiceInterface $saveConfigurationService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_update_saved_search_configuration',
        methods: ['PUT'],
    )]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'saved_search_update_configuration',
        description: 'saved_search_update_configuration_description',
        summary: 'saved_search_update_configuration_summary',
        tags: [Tags::Search->value]
    )]
    #[SavedSearchRequestBody]
    #[IdParameter(type: 'saved search configuration')]
    #[SuccessResponse(
        description: 'saved_search_update_configuration_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateConfiguration(
        #[MapRequestPayload] SavedSearchParameter $parameter,
        int $id
    ): Response {
        $this->saveConfigurationService->updateConfiguration($parameter, $id);

        return new Response();
    }
}
