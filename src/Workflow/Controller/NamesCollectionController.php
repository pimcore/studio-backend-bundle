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

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Content\WorkflowStringListContent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowMetaServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class NamesCollectionController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly WorkflowMetaServiceInterface $workflowMetaService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/workflows/names', name: 'pimcore_studio_api_workflows_names', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::PREFIX . '/workflows/names',
        operationId: 'workflow_get_names',
        description: 'workflow_get_names_description',
        summary: 'workflow_get_names_summary',
        tags: [Tags::Workflows->name]
    )]
    #[SuccessResponse(
        description: 'workflow_get_names_success_response',
        content: new WorkflowStringListContent()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getNames(): JsonResponse
    {
        return $this->jsonResponse([
            'items' => $this->workflowMetaService->getWorkflowNames(),
        ]);
    }
}
