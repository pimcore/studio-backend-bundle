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

use League\OAuth2\Server\Exception\OAuthServerException;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationRedirect;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationRequestValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactory;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\UserEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Request\SingleParameterRequestBody;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User as SecurityUser;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use function rawurlencode;
use function str_contains;

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
        private readonly PendingAuthorizationStore $pendingAuthorizationStore,
        private readonly AuthorizationRequestValidator $authorizationRequestValidator,
        private readonly AuthorizationServerFactory $authorizationServerFactory,
        private readonly ResponseFactoryInterface $psrResponseFactory,
        private readonly Security $security,
        private readonly ?string $issuer,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_oauth_authorization_approve', methods: ['POST'])]
    #[Post(
        path: self::PREFIX . self::ROUTE,
        operationId: 'oauth_authorization_approve',
        description: 'Approve or deny a pending OAuth authorization and get the redirect location',
        summary: 'Complete OAuth authorization',
        tags: [Tags::Oauth->value],
    )]
    #[StringParameter('id', 'a1b2c3', 'Opaque id of the pending authorization')]
    #[SingleParameterRequestBody('approved', true, 'boolean')]
    #[SuccessResponse(
        description: 'The location to redirect the browser to',
        content: new JsonContent(ref: AuthorizationRedirect::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function __invoke(string $id, Request $request): Response
    {
        $params = $this->pendingAuthorizationStore->get($id);
        if ($params === null) {
            throw new NotFoundException('authorization', $id);
        }

        $user = $this->security->getUser();
        if (!$user instanceof SecurityUser) {
            throw new NotFoundException('authorization', $id);
        }

        $authorizationRequest = $this->authorizationRequestValidator->validate($params);
        if ($authorizationRequest === null) {
            throw new NotFoundException('authorization', $id);
        }

        try {
            $body = $request->toArray();
        } catch (Throwable) {
            $body = [];
        }
        $approved = ($body['approved'] ?? false) === true;

        $authorizationRequest->setUser(new UserEntity((string) $user->getId()));
        $authorizationRequest->setAuthorizationApproved($approved);

        try {
            $psrResponse = $this->authorizationServerFactory->create()->completeAuthorizationRequest(
                $authorizationRequest,
                $this->psrResponseFactory->createResponse(),
            );
        } catch (OAuthServerException $exception) {
            // A denied request surfaces as an access_denied redirect.
            $psrResponse = $exception->generateHttpResponse($this->psrResponseFactory->createResponse());
        }

        $this->pendingAuthorizationStore->remove($id);

        return $this->jsonResponse(
            new AuthorizationRedirect($this->withIssuer($psrResponse->getHeaderLine('Location'), $request)),
        );
    }

    private function withIssuer(string $location, Request $request): string
    {
        if ($location === '') {
            return $location;
        }

        // RFC 9207: identify the issuer in the authorization response.
        $issuer = $this->issuer ?? $request->getSchemeAndHttpHost();
        $separator = str_contains($location, '?') ? '&' : '?';

        return $location . $separator . 'iss=' . rawurlencode($issuer);
    }
}
