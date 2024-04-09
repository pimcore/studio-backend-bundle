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

use Pimcore\Bundle\StudioApiBundle\Service\OpenApiServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

final class OpenApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        private readonly OpenApiServiceInterface $openApiService)
    {
        parent::__construct($serializer);
    }

    #[Route('/docs', name: 'pimcore_studio_api_docs', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('@PimcoreStudioApi/swagger-ui/index.html.twig');
    }

    #[Route('/docs.json', name: 'pimcore_studio_api_docs_json', methods: ['GET'])]
    public function openapi(): JsonResponse
    {
        return new JsonResponse($this->openApiService->getConfig());
    }
}