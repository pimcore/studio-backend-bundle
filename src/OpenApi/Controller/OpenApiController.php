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

namespace Pimcore\Bundle\StudioBackendBundle\OpenApi\Controller;

use JsonException;
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

    #[Route('/docs.json', name: 'pimcore_studio_api_docs_json', methods: ['GET'])]
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
