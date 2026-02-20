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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\FieldCollection;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Request\FieldCollectionUpdateRequestBody;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\UpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\FieldCollectionDetail;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\FieldCollectionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
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
    private const string ROUTE = '/class/field-collection/{key}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly FieldCollectionServiceInterface $fieldCollectionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|NotFoundException|NotWriteableException
     */
    #[Route(
        self::ROUTE,
        name: 'pimcore_studio_api_class_field_collection_update',
        methods: ['PUT']
    )]
    #[IsGranted(UserPermissions::FIELD_COLLECTIONS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_field_collection_update',
        description: 'class_field_collection_update_description',
        summary: 'class_field_collection_update_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'key',
        example: 'MyFieldCollection',
        description: 'Field collection unique key',
        required: true
    )]
    #[FieldCollectionUpdateRequestBody]
    #[SuccessResponse(
        description: 'class_field_collection_update_success_response',
        content: new JsonContent(ref: FieldCollectionDetail::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function updateFieldCollection(
        string $key,
        #[MapRequestPayload] UpdateParameters $parameters,
    ): JsonResponse {
        return $this->jsonResponse(
            $this->fieldCollectionService->updateFieldCollection($key, $parameters)
        );
    }
}
