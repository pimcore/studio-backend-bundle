<?php

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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
abstract class AbstractApiController extends AbstractController
{
    public function __construct(private readonly SerializerInterface $serializer)
    {

    }

    protected function jsonLd(mixed $resource): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($resource, 'jsonld'), 200, [], true);
    }
}
