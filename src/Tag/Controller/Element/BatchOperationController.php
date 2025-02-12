<?php
declare(strict_types=1);

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 *  @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

namespace Pimcore\Bundle\StudioBackendBundle\Tag\Controller\Element;

use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Tag\Attribute\Parameter\Path\BatchOperationParameter;
use Pimcore\Bundle\StudioBackendBundle\Tag\MappedParameter\BatchOperationParameters;
use Pimcore\Bundle\StudioBackendBundle\Tag\Service\ExecutionEngine\BatchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class BatchOperationController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly BatchServiceInterface $batchService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|ForbiddenException|NotFoundException
     */
    #[Route(
        '/tags/batch/{operation}/{elementType}/{id}',
        name: 'pimcore_studio_api_batch_assign_elements_tags',
        methods: ['POST']
    )]
    #[IsGranted(UserPermissions::TAGS_ASSIGNMENT->value)]
    #[Post(
        path: self::PREFIX . '/tags/batch/{operation}/{elementType}/{id}',
        operationId: 'tag_batch_operation_to_elements_by_type_and_id',
        description: 'tag_batch_operation_to_elements_by_type_and_id_description',
        summary: 'tag_batch_operation_to_elements_by_type_and_id_summary',
        tags: [Tags::TagsForElement->value]
    )]
    #[IdParameter]
    #[ElementTypeParameter]
    #[BatchOperationParameter]
    #[CreatedResponse(
        description: 'tag_batch_operation_to_elements_by_type_and_id_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function assignTag(
        string $elementType,
        int $id,
        string $operation,
    ): JsonResponse {

        return $this->jsonResponse(
            [
                'jobRunId' => $this->batchService->createJobRunForBatchOperation(
                    new BatchOperationParameters($elementType, $id, $operation)
                ),
            ],
            HttpResponseCodes::CREATED->value
        );
    }
}
