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

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectAddParameters;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RedirectsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ForbiddenException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotWriteableException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\ReferenceRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\UserPermissions;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @internal
 */
final class AddController extends AbstractApiController
{
    private const string ROUTE = '/bundle/seo/redirects/add';

    public function __construct(
        private readonly RedirectsServiceInterface $redirectsService,
        SerializerInterface $serializer,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_redirects_add', methods: ['POST'])]
    #[IsGranted(UserPermissions::REDIRECTS->value)]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_redirect_add',
        description: 'bundle_seo_redirect_add_description',
        summary: 'bundle_seo_redirect_add_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[SuccessResponse(
        description: 'bundle_seo_redirect_add_success_response',
        content: new JsonContent(ref: Redirect::class)
    )]
    #[ReferenceRequestBody(RedirectAddParameters::class)]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function addRedirect(
        #[MapRequestPayload] RedirectAddParameters $parameters
    ): JsonResponse {

        return $this->jsonResponse($this->redirectsService->addRedirect($parameters));
    }
}
