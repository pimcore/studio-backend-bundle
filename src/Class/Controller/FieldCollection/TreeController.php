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
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Response\Property\AnyOfFieldCollectionNodes;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\FieldCollectionTreeParameter;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\FieldCollection\FieldCollectionTreeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\TextFieldParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Pimcore\Bundle\StudioBackendBundle\Util\Trait\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use function count;

/**
 * @internal
 */
final class TreeController extends AbstractApiController
{
    use PaginatedResponseTrait;

    private const string ROUTE = '/class/field-collection/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly FieldCollectionTreeServiceInterface $fieldCollectionTreeService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_field_collection_tree', methods: ['GET'], priority: 10)]
    #[IsGranted(UserPermissions::FIELD_COLLECTIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_field_collection_get_tree',
        description: 'class_field_collection_get_tree_description',
        summary: 'class_field_collection_get_tree_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[TextFieldParameter(
        name: 'allowedTypes',
        description: 'Comma-separated list of allowed field collection types to filter by.',
        example: 'NewsCars,NewsText'
    )]
    #[SuccessResponse(
        description: 'class_field_collection_get_tree_success_response',
        content: new CollectionJson(new AnyOfFieldCollectionNodes())
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getFieldCollectionTree(
        #[MapQueryString] FieldCollectionTreeParameter $parameters = new FieldCollectionTreeParameter()
    ): JsonResponse {
        $definitions = $this->fieldCollectionTreeService->getTree($parameters->getAllowedTypesArray());

        return $this->getPaginatedCollection(
            $this->serializer,
            $definitions,
            count($definitions)
        );
    }
}
