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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\NoContentResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Schema\OutboxBatch;
use Pimcore\Bundle\StudioBackendBundle\Telemetry\Service\TelemetryServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class OutboxController extends AbstractApiController
{
    private const string ROUTE = '/telemetry/outbox';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TelemetryServiceInterface $telemetryService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_telemetry_outbox', methods: ['GET'])]
    #[IsGranted(UserPermissions::PIMCORE_USER->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'telemetry_outbox_next_batch',
        description: 'telemetry_outbox_next_batch_description',
        summary: 'telemetry_outbox_next_batch_summary',
        tags: [Tags::Telemetry->value]
    )]
    #[SuccessResponse(
        description: 'telemetry_outbox_next_batch_success_response',
        content: new JsonContent(ref: OutboxBatch::class)
    )]
    #[NoContentResponse(description: 'telemetry_outbox_next_batch_no_content_response')]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getNextBatch(): Response
    {
        $batch = $this->telemetryService->getNextBatch();

        if (!$batch instanceof OutboxBatch) {
            return new Response(status: HttpResponseCodes::NO_CONTENT->value);
        }

        return $this->jsonResponse($batch);
    }
}
