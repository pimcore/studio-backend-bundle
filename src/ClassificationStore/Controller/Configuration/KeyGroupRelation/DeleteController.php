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

namespace Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Controller\Configuration\KeyGroupRelation;

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Schema\Configuration\KeyGroupRelationDelete;
use Pimcore\Bundle\StudioBackendBundle\ClassificationStore\Service\Configuration\KeyGroupRelationServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
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
final class DeleteController extends AbstractApiController
{
    private const string ROUTE = '/classification-store/configuration/key-group-relations';

    public function __construct(
        SerializerInterface $serializer,
        private readonly KeyGroupRelationServiceInterface $keyGroupRelationService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_cs_configuration_key_group_relation_delete', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::CLASSIFICATION_STORE->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'classification_store_configuration_key_group_relation_delete',
        description: 'classification_store_configuration_key_group_relation_delete_description',
        summary: 'classification_store_configuration_key_group_relation_delete_summary',
        tags: [Tags::ClassificationStore->value]
    )]
    #[ReferenceRequestBody(KeyGroupRelationDelete::class)]
    #[SuccessResponse(
        description: 'classification_store_configuration_key_group_relation_delete_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function deleteKeyGroupRelation(
        #[MapRequestPayload] KeyGroupRelationDelete $parameters
    ): Response {
        $this->keyGroupRelationService->deleteKeyGroupRelation(
            $parameters->getKeyId(),
            $parameters->getGroupId()
        );

        return new Response();
    }
}
