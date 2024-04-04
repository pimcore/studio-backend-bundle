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

namespace Pimcore\Bundle\StudioApiBundle\Controller\Api;

use Hateoas\Representation\CollectionRepresentation;
use Hateoas\Representation\PaginatedRepresentation;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/studio/api')]
abstract class AbstractApiController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer)
    {

    }

    protected function getPaginatedCollection(
        string $route,
        array $data,
        int $page = 1,
        int $limit = 10,
        int $totalItems = 0
    ): JsonResponse {
        $paginatedCollection = new PaginatedRepresentation(
            new CollectionRepresentation($data),
            $route, // route
            [], // route parameters
            $page,       // page number
            $limit,      // limit
            (int)($totalItems / $limit),       // total pages
            'page',  // page route parameter name, optional, defaults to 'page'
            'limit', // limit route parameter name, optional, defaults to 'limit'
            false,   // generate relative URIs, optional, defaults to `false`
            $totalItems       // total collection size, optional, defaults to `null`
        );
        $serialized = $this->serializer->serialize($paginatedCollection, 'json');

        return new JsonResponse($serialized, 200, [], true);
    }

    protected function jsonHal(mixed $data): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json'), 200, [], true);
    }
}
