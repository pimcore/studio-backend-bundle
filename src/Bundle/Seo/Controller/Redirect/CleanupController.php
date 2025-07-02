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

use OpenApi\Attributes\Delete;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RedirectsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class CleanupController extends AbstractApiController
{
    private const string ROUTE = '/bundle/seo/redirects/cleanup';

    public function __construct(
        private readonly RedirectsServiceInterface $redirectsService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_redirects_cleanup', methods: ['DELETE'])]
    #[IsGranted(UserPermissions::REDIRECTS->value)]
    #[Delete(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_redirect_cleanup',
        description: 'bundle_seo_redirect_cleanup_description',
        summary: 'bundle_seo_redirect_cleanup_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[SuccessResponse(
        description: 'bundle_seo_redirect_cleanup_success_response',
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function cleanupRedirects(): Response
    {
        $this->redirectsService->cleanupRedirects();

        return new Response();
    }
}
