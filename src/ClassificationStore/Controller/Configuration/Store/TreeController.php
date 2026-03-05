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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\StoreTreeNode;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\StoreServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class TreeController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/stores/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly StoreServiceInterface $storeService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_cs_configuration_store_tree',
        methods: ['GET'],
        priority: 10
    )]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_store_tree',
        description: 'classification_store_configuration_store_tree_description',
        summary: 'classification_store_configuration_store_tree_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[SuccessResponse(
        description: 'classification_store_configuration_store_tree_success_response',
        content: new JsonContent(type: 'array', items: new Items(ref: StoreTreeNode::class))
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getStoreTree(): JsonResponse
    {
        return $this->jsonResponse($this->storeService->getStoreTree());
    }
}
