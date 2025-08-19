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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Delete;
use OpenApi\Attributes\RequestBody;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementDeleteServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementDeletionFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidElementTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\IdsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Content\ScalarItemsJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\CreatedResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class BatchDeleteController extends AbstractApiController
{
    private const string ROUTE = '/assets/batch-delete';
    
    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementDeleteServiceInterface $elementDeleteService,
        private readonly SecurityServiceInterface $securityService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementDeletionFailedException
     * @throws ForbiddenException
     * @throws InvalidElementTypeException
     * @throws NotFoundException
     * @throws UserNotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_assets_batch_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::ASSETS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'asset_batch_delete',
        description: 'asset_batch_delete_description',
        summary: 'asset_batch_delete_summary',
        tags: [Tags::Assets->value]
    )]
    #[RequestBody(
        content: new ScalarItemsJson('integer', 'ids')
    )]
    #[CreatedResponse(
        description: 'asset_batch_delete_created_response',
        content: new IdJson('ID of created jobRun', 'jobRunId')
    )]
    #[DefaultResponses([
        HttpResponseCodes::FORBIDDEN,
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function assetBatchDelete(#[MapRequestPayload] IdsParameter $parameter): Response
    {

        return $this->jsonResponse(
            [
                'jobRunId' => $this->elementDeleteService->batchDeleteElements(
                    $parameter,
                    ElementTypes::TYPE_ASSET,
                    $this->securityService->getCurrentUser()
                )
            ],
            HttpResponseCodes::CREATED->value
        );

    }
}
