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

namespace Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Schema\JobRun;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ExecutionEngineServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\ItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListRunningController extends AbstractApiController
{
    private const string ROUTE = '/execution-engine/running-jobs';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ExecutionEngineServiceInterface $engineService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_execution_engine_list_running', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'execution_engine_list_running_jobs',
        description: 'execution_engine_list_running_jobs_description',
        summary: 'execution_engine_list_running_jobs_summary',
        tags: [Tags::ExecutionEngine->value]
    )]
    #[SuccessResponse(
        description: 'execution_engine_list_running_jobs_success_response',
        content: new ItemsJson(JobRun::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getRunningJobsList(): JsonResponse
    {
        return $this->jsonResponse(['items' => $this->engineService->listRunningJobRuns()]);
    }
}
