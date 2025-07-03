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

namespace Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Controller\Redirect;

use OpenApi\Attributes\Get;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Attribute\Content\PrioritiesJson;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RedirectsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class ListPrioritiesController extends AbstractApiController
{
    private const string ROUTE = '/bundle/seo/redirects/priorities';

    public function __construct(
        private readonly RedirectsServiceInterface $redirectsService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_redirects_list_priorities', methods: ['GET'])]
    #[IsGranted(UserPermissions::REDIRECTS->value)]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_redirect_list_priorities',
        description: 'bundle_seo_redirect_list_priorities_description',
        summary: 'bundle_seo_redirect_list_priorities_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[SuccessResponse(
        description: 'bundle_seo_redirect_list_priorities_success_response',
        content: new PrioritiesJson(),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
    ])]
    public function listPriorities(): JsonResponse
    {
        return $this->jsonResponse(['priorities' => $this->redirectsService->listPriorities()]);
    }
}
