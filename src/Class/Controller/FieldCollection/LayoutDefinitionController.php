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

use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Class\Schema\FieldCollection\ConfigLayoutDefinition;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\LayoutDefinitionServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\EnvironmentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
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
final class LayoutDefinitionController extends AbstractApiController
{
    private const string ROUTE = '/class/field-collection/{key}/layout';

    public function __construct(
        SerializerInterface $serializer,
        private readonly LayoutDefinitionServiceInterface $layoutDefinitionService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException|EnvironmentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_field_collection_get_layout_by_key', methods: ['GET'])]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_field_collection_get_layout_by_key',
        description: 'class_field_collection_get_layout_by_key_description',
        summary: 'class_field_collection_get_layout_by_key_summary',
        tags: [Tags::ClassDefinition->value],
    )]
    #[StringParameter(
        name: 'key',
        example: 'MyFieldCollection',
        description: 'Field collection unique key',
        required: true
    )]
    #[SuccessResponse(
        description: 'class_field_collection_get_layout_by_key_success_response',
        content: new JsonContent(ref: ConfigLayoutDefinition::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::NOT_FOUND,
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function getLayoutDefinitionByKey(string $key): JsonResponse
    {
        return $this->jsonResponse(
            $this->layoutDefinitionService->getLayoutDefinitionByKey($key)
        );
    }
}
