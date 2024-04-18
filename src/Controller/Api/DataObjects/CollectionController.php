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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api\DataObjects;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ClassIdParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ExcludeFoldersParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\IdSearchTermParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PageParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PageSizeParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\ParentIdParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathIncludeDescendantsParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathIncludeParentParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Parameters\Query\PathParameter;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Content\CollectionJson;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\Property\DataObjectCollection;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\SuccessResponse;
use Pimcore\Bundle\StudioApiBundle\Attributes\Response\UnauthorizedResponse;
use Pimcore\Bundle\StudioApiBundle\Config\Tags;
use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Exception\InvalidQueryTypeException;
use Pimcore\Bundle\StudioApiBundle\Request\Query\Filter\DataObjectParameters;
use Pimcore\Bundle\StudioApiBundle\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\Filter\FilterServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly DataObjectSearchServiceInterface $dataObjectSearchService,
        private readonly FilterServiceInterface $filterService
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws InvalidQueryTypeException
     */
    #[Route('/data-objects', name: 'pimcore_studio_api_data_objects', methods: ['GET'])]
    //#[IsGranted(self::VOTER_STUDIO_API)]
    #[GET(
        path: self::API_PATH . '/data-objects',
        operationId: 'getDataObjects',
        description: 'Get paginated data objects',
        summary: 'Get all DataObjects',
        security: self::SECURITY_SCHEME,
        tags: [Tags::DataObjects->name],
    )]
    #[PageParameter]
    #[PageSizeParameter]
    #[ParentIdParameter]
    #[IdSearchTermParameter]
    #[ExcludeFoldersParameter]
    #[PathParameter]
    #[PathIncludeParentParameter]
    #[PathIncludeDescendantsParameter]
    #[ClassIdParameter]
    #[SuccessResponse(
        description: 'Paginated data objects with total count as header param',
        content: new CollectionJson(new DataObjectCollection())
    )]
    #[UnauthorizedResponse]
    public function getDataObjects(#[MapQueryString] DataObjectParameters $parameters): JsonResponse
    {

        /** @var DataObjectQuery $dataObjectQuery */
        $dataObjectQuery = $this->filterService->applyFilters($parameters, 'dataObject');

        $result = $this->dataObjectSearchService->searchDataObjects($dataObjectQuery);

        return $this->getPaginatedCollection(
            $this->serializer,
            $result->getItems(),
            $result->getTotalItems()
        );
    }
}
