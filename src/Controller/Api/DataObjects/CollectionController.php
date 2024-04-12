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

use Pimcore\Bundle\StudioApiBundle\Controller\Api\AbstractApiController;
use Pimcore\Bundle\StudioApiBundle\Controller\Trait\PaginatedResponseTrait;
use Pimcore\Bundle\StudioApiBundle\Dto\Collection;
use Pimcore\Bundle\StudioApiBundle\Service\DataObjectSearchServiceInterface;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQuery;
use Pimcore\Bundle\StudioApiBundle\Service\GenericData\V1\DataObjectQueryProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class CollectionController extends AbstractApiController
{
    use PaginatedResponseTrait;

    public function __construct(
        SerializerInterface $serializer,
        private readonly DataObjectQueryProviderInterface $dataObjectQueryProvider,
        private readonly DataObjectSearchServiceInterface $dataObjectSearchService,
    ) {
        parent::__construct($serializer);
    }

    #[Route('/data-objects', name: 'pimcore_studio_api_data_objects', methods: ['GET'])]
    #[IsGranted(self::VOTER_STUDIO_API)]
    public function getAssets(#[MapQueryString] Collection $collection): JsonResponse
    {

        $dataObjectQuery = $this->getDataQuery()
            ->setPage($collection->getPage())
            ->setPageSize($collection->getPageSize())
            ->setClassDefinitionId('EV');
        $result = $this->dataObjectSearchService->searchDataObjects($dataObjectQuery);

        return $this->getPaginatedCollection(
            $this->serializer,
            $result->getItems(),
            $result->getTotalItems()
        );
    }

    private function getDataQuery(): DataObjectQuery
    {
        return $this->dataObjectQueryProvider->createDataObjectQuery();
    }
}
