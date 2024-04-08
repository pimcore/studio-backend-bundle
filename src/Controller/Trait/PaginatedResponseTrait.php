<?php

namespace Pimcore\Bundle\StudioApiBundle\Controller\Trait;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

trait PaginatedResponseTrait
{
    private const HEADER_TOTAL_ITEMS = 'X-Pimcore-Total-Items';

    protected function getPaginatedCollection(
        SerializerInterface $serializer,
        array $data,
        int $totalItems = 0
    ): JsonResponse {
        $serialized = $serializer->serialize($data, 'json');

        return new JsonResponse($serialized, 200, [self::HEADER_TOTAL_ITEMS => $totalItems], true);
    }
}