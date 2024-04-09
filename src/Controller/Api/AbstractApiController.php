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

use OpenApi\Attributes\Info;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/studio/api')]
abstract class AbstractApiController extends AbstractController
{
    public const VOTER_STUDIO_API = 'STUDIO_API';
    public const VOTER_PUBLIC_STUDIO_API = 'PUBLIC_STUDIO_API';

    public const API_PATH = '/studio/api';

    public function __construct(protected readonly SerializerInterface $serializer)
    {

    }

    protected function jsonResponse(mixed $data, array $headers = []): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, 'json'), 200, $headers, true);
    }
}
