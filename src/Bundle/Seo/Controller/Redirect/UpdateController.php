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
use OpenApi\Attributes\Put;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\Redirect;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Schema\RedirectUpdateParameters;
use Pimcore\Bundle\StudioBackendBundle\Bundle\Seo\Service\RedirectsServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\ElementSavingFailedException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\IdParameter;
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
final class UpdateController extends AbstractApiController
{
    private const string ROUTE = '/bundle/seo/redirects/{id}';

    public function __construct(
        private readonly RedirectsServiceInterface $redirectsService,
        SerializerInterface $serializer
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws ElementSavingFailedException|InvalidArgumentException|NotFoundException
     */
    #[Route(self::ROUTE, name: 'pimcore_studio_api_bundle_seo_redirects_update', methods: ['PUT'])]
    #[IsGranted(UserPermissions::REDIRECTS->value)]
    #[Put(
        path: self::PREFIX . self::ROUTE,
        operationId: 'bundle_seo_redirect_update_by_id',
        description: 'bundle_seo_redirect_update_by_id_description',
        summary: 'bundle_seo_redirect_update_by_id_summary',
        tags: [Tags::BundleSeo->value]
    )]
    #[IdParameter(type: 'redirect')]
    #[ReferenceRequestBody(RedirectUpdateParameters::class)]
    #[SuccessResponse(
        description: 'bundle_seo_redirect_update_by_id_success_response',
        content: new JsonContent(ref: Redirect::class)
    )]
    #[DefaultResponses([
        HttpResponseCodes::INTERNAL_SERVER_ERROR,
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function updateRedirect(
        int $id,
        #[MapRequestPayload] RedirectUpdateParameters $parameters
    ): JsonResponse {

        return $this->jsonResponse($this->redirectsService->updateRedirect($id, $parameters));
    }
}
