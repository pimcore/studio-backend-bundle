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

namespace Pimcore\Bundle\StudioBackendBundle\IndexSync\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\IndexSync\Service\JobServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class StartController extends AbstractApiController
{
    private const string ROUTE = '/index-sync';

    public function __construct(
        SerializerInterface $serializer,
        private readonly JobServiceInterface $jobService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_index_sync_start', methods: ['POST'])]
    #[IsGranted(UserPermissions::SYSTEM_SETTINGS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'index_sync_start',
        description: 'index_sync_start_description',
        summary: 'index_sync_start_summary',
        tags: [Tags::IndexSync->value]
    )]
    #[CreatedResponse(
        description: 'index_sync_start_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function startIndexSync(): Response
    {
        return $this->jsonResponse(
            ['jobRunId' => $this->jobService->createSyncJob()],
            HttpResponseCodes::CREATED->value
        );
    }
}
