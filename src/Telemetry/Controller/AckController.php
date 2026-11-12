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

namespace Pimcore\Bundle\StudioBackendBundle\Telemetry\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckParameters;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxAckResult;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Service\TelemetryServiceInterface;
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
final class AckController extends AbstractApiController
{
    private const string ROUTE = '/telemetry/outbox/ack';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TelemetryServiceInterface $telemetryService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_telemetry_outbox_ack', methods: ['POST'])]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'telemetry_outbox_ack',
        description: 'telemetry_outbox_ack_description',
        summary: 'telemetry_outbox_ack_summary',
        tags: [Tags::Telemetry->value]
    )]
    #[ReferenceRequestBody(OutboxAckParameters::class)]
    #[SuccessResponse(
        description: 'telemetry_outbox_ack_success_response',
        content: new JsonContent(ref: OutboxAckResult::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function ackBatch(
        #[MapRequestPayload] OutboxAckParameters $parameters
    ): JsonResponse {
        return $this->jsonResponse($this->telemetryService->ackBatch($parameters->getNonce()));
    }
}
