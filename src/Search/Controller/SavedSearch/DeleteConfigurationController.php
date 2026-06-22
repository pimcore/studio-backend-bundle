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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Search\Service\SavedSearchConfigurationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DeleteConfigurationController extends AbstractApiController
{
    private const string ROUTE = '/search/saved/configuration/delete/{configurationId}';

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
        name: 'pimcore_studio_api_delete_saved_search_configuration',
        methods: ['PUT'],
    )]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'saved_search_delete_configuration',
        description: 'saved_search_delete_configuration_description',
        summary: 'saved_search_delete_configuration_summary',
        tags: [Tags::Search->value]
    )]
    #[IdParameter(
        type: 'configurationId',
        name: 'configurationId'
    )]
    #[SuccessResponse(
        description: 'saved_search_delete_configuration_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function deleteConfiguration(int $configurationId): Response {
        $this->saveConfigurationService->deleteConfiguration($configurationId);

        return new Response();
    }
}
