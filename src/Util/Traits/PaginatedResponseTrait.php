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

namespace Pimcore\Bundle\StudioApiBundle\Util\Traits;

use Pimcore\Bundle\StudioApiBundle\Response\Collection;
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
        array $data,
        int $totalItems = 0
    ): JsonResponse {
        $serialized = $serializer->serialize(new Collection($totalItems, $data), 'json');

        return new JsonResponse($serialized, 200, [self::HEADER_TOTAL_ITEMS => $totalItems], true);
    }
}
