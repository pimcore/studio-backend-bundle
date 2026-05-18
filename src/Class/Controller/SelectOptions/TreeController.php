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

namespace Pimcore\Bundle\StudioBackendBundle\Class\Controller\SelectOptions;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Class\Attribute\Response\Property\AnyOfSelectOptionNodes;
use Pimcore\Bundle\StudioBackendBundle\Class\MappedParameter\TreeParameter;
use Pimcore\Bundle\StudioBackendBundle\Class\Service\SelectOptions\TreeServiceInterface;
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

    private const string ROUTE = '/class/select-option/tree';

    public function __construct(
        SerializerInterface $serializer,
        private readonly TreeServiceInterface $optionService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_class_select_option_tree', methods: ['GET'], priority: 10)]
    #[IsGranted(UserPermissions::SELECT_OPTIONS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'class_select_option_get_tree',
        description: 'class_select_option_get_tree_description',
        summary: 'class_select_option_get_tree_summary',
        tags: [Tags::ClassDefinition->value]
    )]
    #[BoolParameter('withGroup', description: 'Whether to group the results.', example: true)]
    #[SuccessResponse(
        description: 'class_select_option_get_tree_success_response',
        content: new CollectionJson(new AnyOfSelectOptionNodes())
    )]
    #[DefaultResponses([HttpResponseCodes::UNAUTHORIZED])]
    public function getTree(#[MapQueryString] TreeParameter $parameters): JsonResponse
    {
        $definitions = $this->optionService->getTree($parameters->isWithGroup());

        return $this->getPaginatedCollection(
            $this->serializer,
            $definitions,
            count($definitions)
        );
    }
}
