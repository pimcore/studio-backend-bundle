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
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Attribute\Content\PrioritiesJson;
use Pimcore\Bundle\StudioBackendBundle\Bundle\ApplicationLogger\Service\LogServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
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
final class ListPrioritiesController extends AbstractApiController
{
    private const string ROUTE = '/bundle/application-logger/priorities';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LogServiceInterface $logService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_application_logger_list_priorities', methods: ['GET'])]
    #[IsGranted(UserPermissions::APPLICATION_LOGGING->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_application_logger_list_priorities',
        description: 'bundle_application_logger_list_priorities_description',
        summary: 'bundle_application_logger_list_priorities_summary',
        tags: [Tags::BundleApplicationLogger->value]
    )]
    #[SuccessResponse(
        description: 'bundle_application_logger_list_priorities_success_response',
        content: new PrioritiesJson(),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listPriorities(): JsonResponse
    {
        return $this->jsonResponse(['priorities' => $this->logService->listPriorities()]);
    }
}
