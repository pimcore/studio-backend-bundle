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

use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\DatabaseException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\MappedParameter\HideJobRunsParameter;
use Pimcore\Bundle\StudioBackendBundle\ExecutionEngine\Service\ExecutionEngineServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ScalarItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class HideController extends AbstractApiController
{
    private const string ROUTE = '/execution-engine/hide';

    public function __construct(
        private readonly ExecutionEngineServiceInterface $executionEngineService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws DatabaseException|ForbiddenException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_execution_engine_hide_jobs', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'execution_engine_hide_job_runs',
        description: 'execution_engine_hide_job_runs_description',
        summary: 'execution_engine_hide_job_runs_summary',
        tags: [Tags::ExecutionEngine->value]
    )]
    #[RequestBody(
        content: new ScalarItemsJson('integer', 'jobRunIds')
    )]
    #[SuccessResponse]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function hideJobRuns(#[MapRequestPayload] HideJobRunsParameter $parameter): Response
    {
        $this->executionEngineService->hideAction($parameter);

        return new Response();
    }
}
