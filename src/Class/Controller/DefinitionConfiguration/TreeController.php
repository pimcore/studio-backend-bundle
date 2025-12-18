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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\DefinitionConfiguration;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Response\Property\AnyOfClassDefinitionNodes;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\ClassDefinitionTreeParameter;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\ClassDefinitionTreeServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\BoolParameter;
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

    private const string ROUTE = '/class/definition/configuration-view/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly ClassDefinitionTreeServiceInterface $treeService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_classes_tree', methods: ['GET'])]
    #[IsGranted(UserPermissions::CLASS_DEFINITION->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_definition_get_tree',
        description: 'class_definition_get_tree_description',
        summary: 'class_definition_get_tree_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[BoolParameter('withGroup', description: 'Whether to group the results.', example: true)]
    #[SuccessResponse(
        description: 'class_definition_get_tree_success_response',
        content: new CollectionJson(new AnyOfClassDefinitionNodes())
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getClassDefinitionTree(#[MapQueryString] ClassDefinitionTreeParameter $parameters): JsonResponse
    {
        $definitions = $this->treeService->getTree($parameters->isWithGroup());

        return $this->getPaginatedCollection(
            $this->serializer,
            $definitions,
            count($definitions)
        );
    }
}
