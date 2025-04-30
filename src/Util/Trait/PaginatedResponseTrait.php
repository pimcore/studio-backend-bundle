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

namespace Pimcore\Bundle\StudioBackendBundle\Util\Trait;

use Pimcore\Bundle\StudioBackendBundle\Response\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
trait PaginatedResponseTrait
{
    private const HEADER_TOTAL_ITEMS = 'X-Pimcore-Total-Items';

    protected function getPaginatedCollection(
        SerializerInterface $serializer,
        array $items,
        int $totalItems = 0
    ): JsonResponse {
        $serialized = $serializer->serialize(new Collection($totalItems, $items), 'json');

        return new JsonResponse($serialized, 200, [self::HEADER_TOTAL_ITEMS => $totalItems], true);
    }
}
