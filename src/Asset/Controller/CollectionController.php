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

namespace Pimcore\Bundle\StudioBackendBundle\Asset\Controller;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Asset\Attributes\Response\Property\AnyOfAsset;
use Pimcore\Bundle\StudioBackendBundle\Asset\Service\AssetServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\AssetSearchServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\OpenSearchFilterInterface;
use Pimcore\Bundle\StudioBackendBundle\DataIndex\Request\ElementParameters;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterServiceTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidFilterTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioBackendBundle\Exception\SearchException;
use Pimcore\Bundle\StudioBackendBundle\Filter\Service\FilterServiceProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ExcludeFoldersParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\IdSearchTermParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathIncludeDescendantsParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathIncludeParentParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Parameters\Query\PathParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\ElementTypes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constants\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Traits\PaginatedResponseTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly AssetServiceInterface $assetService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidFilterServiceTypeException|SearchException|InvalidQueryTypeException|InvalidFilterTypeException
     */
    #[Route('/assets', name: 'pimcore_studio_api_assets', methods: ['GET'])]
    //#[IsGranted('STUDIO_API')]
    //#[IsGranted(UserPermissions::ASSETS->value)]
    #[Get(
        path: self::API_PATH . '/assets',
        operationId: 'getAssets',
        description: 'Get paginated assets',
        summary: 'Get all assets',
        tags: [Tags::Assets->name]
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[ParentIdParameter]
    #[IdSearchTermParameter]
    #[ExcludeFoldersParameter]
    #[PathParameter]
    #[PathIncludeParentParameter]
    #[PathIncludeDescendantsParameter]
    #[SuccessResponse(
        description: 'Paginated assets with total count as header param',
        content: new CollectionJson(new AnyOfAsset())
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED
    ])]
    public function getAssets(#[MapQueryString] ElementParameters $parameters): JsonResponse
    {
        $collection = $this->assetService->getAssets($parameters);

        return $this->getPaginatedCollection(
            $this->serializer,
            $collection->getItems(),
            $collection->getTotalItems()
        );
    }
}
