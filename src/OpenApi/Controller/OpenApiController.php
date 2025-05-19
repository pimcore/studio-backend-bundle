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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Controller;

use Pimcore\Bundle\StudioBackendBundle\OpenApi\MappedParameter\LocaleParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Service\OpenApiServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
final class OpenApiController extends AbstractController
{
    public function __construct(private readonly OpenApiServiceInterface $openApiService)
    {
    }

    #[Route('/docs', name: 'pimcore_studio_api_docs', methods: ['GET'], condition: "'dev' === '%kernel.environment%'")]
    public function index(#[MapQueryString] LocaleParameter $localeParameter = new LocaleParameter()): Response
    {
        return $this->render(
            '@PimcoreStudioBackend/swagger-ui/index.html.twig',
            ['locale' => $localeParameter->getLocale()]
        );
    }

    #[Route('/docs/json', name: 'pimcore_studio_api_docs_json', methods: ['GET'])]
    public function openapi(#[MapQueryString] LocaleParameter $localeParameter = new LocaleParameter()): JsonResponse
    {
        $config = $this->openApiService->getConfig();

        return new JsonResponse(
            $this->openApiService->translateConfig(
                $config,
                $localeParameter->getLocale()
            )
        );
    }
}
