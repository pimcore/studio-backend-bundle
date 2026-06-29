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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Property\WorkflowElementCollection;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowElementsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowElementsServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ElementsController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/workflows/elements/{elementType}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly WorkflowElementsServiceInterface $workflowElementsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        path: self::ROUTE,
        name: 'pimcore_studio_api_element_workflows_elements',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'workflow_get_elements',
        description: 'workflow_get_elements_description',
        summary: 'workflow_get_elements_summary',
        tags: [Tags::Workflows->name]
    )]
    #[ElementTypeParameter]
    #[StringParameter('workflowName', 'product_workflow', 'Workflow name')]
    #[StringParameter('stateName', 'in_review', 'Workflow state / place name', required: false)]
    #[PageParameter]
    #[PageSizeParameter(50)]
    #[SuccessResponse(
        description: 'workflow_get_elements_success_response',
        content: new CollectionJson(new WorkflowElementCollection())
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
    ])]
    public function getElements(
        string $elementType,
        #[MapQueryString] WorkflowElementsParameters $parameters = new WorkflowElementsParameters()
    ): JsonResponse {
        $collection = $this->workflowElementsService->getElements($parameters, $elementType);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
