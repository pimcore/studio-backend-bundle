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

namespace Pimcore\Bundle\StudioBackendBundle\Workflow\Controller;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Request\WorkflowActionRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Content\WorkflowActionSubmissionJson;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Schema\SubmitAction;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowActionServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class SubmitActionController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly SecurityServiceInterface $securityService,
        private readonly WorkflowActionServiceInterface $workflowActionService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/workflows/action',
        name: 'pimcore_studio_api_workflows_submit_action',
        methods: ['POST']
    )]
    //#[IsGranted('STUDIO_API')]
    #[Post(
        path: self::PREFIX . '/workflows/action',
        operationId: 'workflow_action_submit',
        description: 'workflow_action_submit_description',
        summary: 'workflow_action_submit_summary',
        tags: [Tags::Workflows->name]
    )]
    #[WorkflowActionRequestBody]
    #[SuccessResponse(
        description: 'workflow_action_submit_success_response',
        content: new WorkflowActionSubmissionJson()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function publishVersion(
        #[MapRequestPayload] SubmitAction $parameters
    ): JsonResponse {
        $user = $this->securityService->getCurrentUser();

        return $this->jsonResponse($this->workflowActionService->submitAction($user, $parameters));
    }
}
