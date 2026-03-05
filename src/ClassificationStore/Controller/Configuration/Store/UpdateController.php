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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\Store;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreDetail;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreUpdate;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\StoreServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/stores/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly StoreServiceInterface $storeService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     * @throws InvalidArgumentException
     * @throws ElementSavingFailedException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_store_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_store_update',
        description: 'classification_store_configuration_store_update_description',
        summary: 'classification_store_configuration_store_update_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[IdParameter(type: 'store configuration')]
    #[ReferenceRequestBody(StoreUpdate::class)]
    #[SuccessResponse(
        description: 'classification_store_configuration_store_update_success_response',
        content: new JsonContent(ref: StoreDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateStore(
        int $id,
        #[MapRequestPayload] StoreUpdate $parameters
    ): JsonResponse {
        return $this->jsonResponse($this->storeService->updateStore($id, $parameters));
    }
}
