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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Controller;

use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Attribute\Request\ApproveAuthorizationRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\ApproveAuthorization;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationRedirect;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Service\AuthorizationConsentServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Completes a pending authorization once the user has approved (or denied) it
 * on the consent screen, and returns the location the browser must be sent to
 * (the client redirect URI carrying the code, state and issuer).
 *
 * @internal
 */
final class AuthorizationApprovalController extends AbstractApiController
{
    private const string ROUTE = '/oauth/authorizations/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly AuthorizationConsentServiceInterface $authorizationConsentService,
    ) {
        parent::__construct($serializer);
    }

    /**
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_oauth_authorization_approve', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'oauth_authorization_approve',
        description: 'oauth_authorization_approve_description',
        summary: 'oauth_authorization_approve_summary',
        tags: [Tags::Oauth->value],
    )]
    #[StringParameter('id', 'a1b2c3', 'Opaque id of the pending authorization')]
    #[ApproveAuthorizationRequestBody]
    #[SuccessResponse(
        description: 'oauth_authorization_approve_success_response',
        content: new JsonContent(ref: AuthorizationRedirect::class),
    )]
    // 400 and 422 are not listed: DefaultResponses merges both into every operation
    // already, and naming one again only prints it twice. Both are reachable here -
    // Symfony answers them from the payload mapping below, before the action runs.
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function __invoke(
        string $id,
        #[MapRequestPayload] ApproveAuthorization $approveAuthorization,
    ): Response {
        return $this->jsonResponse(
            $this->authorizationConsentService->completeConsent($id, $approveAuthorization->isApproved()),
        );
    }
}
