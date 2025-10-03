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

namespace Pimcore\Bundle\StudioBackendBundle\Controller;

use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

abstract class AbstractApiController extends AbstractController
{
    public const VOTER_PUBLIC_STUDIO_API = 'PUBLIC_STUDIO_API';

    public const PREFIX = '{prefix}';

    public function __construct(
        protected readonly SerializerInterface $serializer,
    ) {
    }

    protected function jsonResponse(
        mixed $data,
        int $status = HttpResponseCodes::SUCCESS->value,
        array $headers = [],
        array $context = []
    ): JsonResponse {
        return new JsonResponse(
            $this->serializer->serialize($data, 'json', $context),
            $status,
            $headers,
            true
        );
    }

    protected function patchResponse(array $errors = [], array $headers = []): Response
    {
        if (!empty($errors)) {
            return new JsonResponse(
                $this->serializer->serialize($errors, 'json'),
                HttpResponseCodes::MULTI_STATUS->value,
                $headers,
                true
            );
        }

        return new Response();
    }
}
