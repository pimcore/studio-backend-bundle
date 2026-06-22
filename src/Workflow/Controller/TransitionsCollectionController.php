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
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Parameter;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowTransitionsParameters;
use Pimcore\Workflow\Manager;
use Pimcore\Workflow\Transition;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TransitionsCollectionController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly Manager $workflowManager,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/workflows/transitions', name: 'pimcore_studio_api_workflows_transitions', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::PREFIX . '/workflows/transitions',
        operationId: 'workflow_get_transitions',
        description: 'workflow_get_transitions_description',
        summary: 'workflow_get_transitions_summary',
        tags: [Tags::Workflows->name]
    )]
    #[Parameter(
        name: 'workflowName',
        description: 'workflow_get_transitions_workflow_name',
        in: 'query',
        required: true,
        schema: new Schema(type: 'string'),
    )]
    #[Parameter(
        name: 'stateName',
        description: 'workflow_get_transitions_state_name',
        in: 'query',
        required: true,
        schema: new Schema(type: 'string'),
    )]
    #[SuccessResponse(
        description: 'workflow_get_transitions_success_response',
        content: new JsonContent(
            required: ['items'],
            properties: [
                new Property(
                    property: 'items',
                    title: 'items',
                    type: 'array',
                    items: new Items(
                        required: ['name', 'label'],
                        properties: [
                            new Property(property: 'name', type: 'string'),
                            new Property(property: 'label', type: 'string'),
                        ],
                        type: 'object',
                    ),
                ),
            ],
            type: 'object',
        )
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getTransitions(#[MapQueryString] WorkflowTransitionsParameters $parameters): JsonResponse
    {
        $items = [];

        try {
            $workflow = $this->workflowManager->getWorkflowByName($parameters->getWorkflowName());
            if ($workflow !== null) {
                foreach ($workflow->getDefinition()->getTransitions() as $transition) {
                    if (!in_array($parameters->getStateName(), $transition->getFroms(), true)) {
                        continue;
                    }
                    $label = $transition instanceof Transition
                        ? $transition->getLabel()
                        : $transition->getName();
                    $items[] = ['name' => $transition->getName(), 'label' => $label];
                }
            }
        } catch (\Throwable) {
            $items = [];
        }

        return $this->jsonResponse(['items' => $items]);
    }
}
