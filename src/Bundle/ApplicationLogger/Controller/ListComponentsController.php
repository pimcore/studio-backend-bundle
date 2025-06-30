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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Attribute\Content\ComponentsJson;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service\LogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
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
final class ListComponentsController extends AbstractApiController
{
    private const string ROUTE = '/bundle/application-logger/components';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LogServiceInterface $logService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_application_logger_list_components', methods: ['GET'])]
    #[IsGranted(UserPermissions::APPLICATION_LOGGING->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_application_logger_list_components',
        description: 'bundle_application_logger_list_components_description',
        summary: 'bundle_application_logger_list_components_summary',
        tags: [Tags::BundleApplicationLogger->value]
    )]
    #[SuccessResponse(
        description: 'bundle_application_logger_list_components_success_response',
        content: new ComponentsJson(),
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listComponents(): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->logService->listComponents()]);
    }
}
