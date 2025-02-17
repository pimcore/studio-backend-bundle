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

namespace Pimcore\Bundle\StudioBackendBundle\Element\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Element\Request\SearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\ElementServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\MappedParameter\ElementTypeParameter as MappedElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\ElementTypeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Query\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\Content\IdJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ResolveBySearchTermController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly ElementServiceInterface $elementService,
        private readonly SecurityServiceInterface $securityService,
    ) {
        parent::__construct($serializer);
    }

    #[Route(
        '/elements/{elementType}/resolve',
        name: 'pimcore_studio_api_elements_resolve',
        methods: ['GET']
    )]
    #[IsGranted(UserPermissions::ELEMENT_TYPE_PERMISSION->value)]
    #[Get(
        path: self::PREFIX . '/elements/{elementType}/resolve',
        operationId: 'element_resolve_by_search_term',
        description: 'element_resolve_by_search_term_description',
        summary: 'element_resolve_by_search_term_summary',
        tags: [Tags::Elements->name]
    )]
    #[ElementTypeParameter]
    #[StringParameter(
        name: 'searchTerm',
        example: '83',
        description: 'Search term to filter elements by.',
        required: true
    )]
    #[SuccessResponse(
        description: 'element_resolve_response_description',
        content: new IdJson('ID of the element')
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function findBySearchTerm(
        string $elementType,
        #[MapQueryString] SearchTermParameter $searchTerm
    ): JsonResponse {

        return $this->jsonResponse(
            [
                'id' => $this->elementService->resolveBySearchTerm(
                    (new MappedElementTypeParameter($elementType))->getType(),
                    $searchTerm,
                    $this->securityService->getCurrentUser()
                ),
            ]
        );
    }
}
