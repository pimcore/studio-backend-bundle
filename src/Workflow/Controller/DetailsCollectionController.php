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
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Attribute\Response\Content\WorkflowDetailsContent;
use Pimcore\Bundle\StudioBackendBundle\Workflow\MappedParameter\WorkflowDetailsParameters;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Service\WorkflowDetailsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Workflow\Util\Trait\WorkflowLayoutTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class DetailsCollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;
    use WorkflowLayoutTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly SecurityServiceInterface $securityService,
        private readonly WorkflowDetailsServiceInterface $workflowDetailsService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/workflows/details', name: 'pimcore_studio_api_element_workflows_details', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    #[Get(
        path: self::PREFIX . '/workflows/details',
        operationId: 'workflow_get_details',
        description: 'workflow_get_details_description',
        summary: 'workflow_get_details_summary',
        tags: [Tags::Workflows->name]
    )]
    #[IdParameter('ID of the element', 'element')]
    #[ElementTypeParameter]
    #[SuccessResponse(
        description: 'workflow_get_details_success_response',
        content: new WorkflowDetailsContent()
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function getDetails(#[MapQueryString] WorkflowDetailsParameters $parameters): JsonResponse
    {
        $user = $this->securityService->getCurrentUser();
        $details = $this->workflowDetailsService->getWorkflowDetails($parameters, $user);

        return $this->jsonResponse([
            'items' => $details,
            'layoutId' => $this->getLastLayoutId($details),
        ]);
    }
}
