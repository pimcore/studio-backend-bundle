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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Content\WorkflowStringListContent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowPlacesParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowMetaServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class PlacesCollectionController extends AbstractApiController
{
    private const string ROUTE = '/workflows/places';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WorkflowMetaServiceInterface $workflowMetaService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_workflows_places', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'workflow_get_places',
        description: 'workflow_get_places_description',
        summary: 'workflow_get_places_summary',
        tags: [Tags::Workflows->name]
    )]
    #[StringParameter('workflowName', 'product_workflow', 'workflow_get_places_workflow_name')]
    #[SuccessResponse(
        description: 'workflow_get_places_success_response',
        content: new WorkflowStringListContent()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getPlaces(
        #[MapQueryString] WorkflowPlacesParameters $parameters = new WorkflowPlacesParameters()
    ): JsonResponse {
        return $this->jsonResponse([
            'items' => $this->workflowMetaService->getPlaces($parameters->getWorkflowName()),
        ]);
    }
}
