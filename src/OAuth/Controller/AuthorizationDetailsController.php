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

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use Pimcore\Bundle\StudioBackendBundle\Controller\AbstractApiController;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsentClient;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsentUser;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationRequestValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Parameter\Path\StringParameter;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\DefaultResponses;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Attribute\Response\SuccessResponse;
use Pimcore\Bundle\StudioBackendBundle\OpenApi\Config\Tags;
use Pimcore\Bundle\StudioBackendBundle\Util\Constant\HttpResponseCodes;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use function array_map;
use function array_values;

/**
 * Details of a pending authorization for the Studio UI consent screen: which
 * client is asking, for which scopes, and which user would be acting.
 *
 * @internal
 */
final class AuthorizationDetailsController extends AbstractApiController
{
    private const string ROUTE = '/oauth/authorizations/{id}';

    public function __construct(
        SerializerInterface $serializer,
        private readonly PendingAuthorizationStore $pendingAuthorizationStore,
        private readonly AuthorizationRequestValidator $authorizationRequestValidator,
        private readonly Security $security,
    ) {
        parent::__construct($serializer);
    }

    #[Route(path: self::ROUTE, name: 'pimcore_studio_api_oauth_authorization_details', methods: ['GET'])]
    #[Get(
        path: self::PREFIX . self::ROUTE,
        operationId: 'oauth_authorization_details',
        description: 'Details of a pending OAuth authorization for the consent screen',
        summary: 'Get pending OAuth authorization',
        tags: [Tags::Oauth->value],
    )]
    #[StringParameter('id', 'a1b2c3', 'Opaque id of the pending authorization')]
    #[SuccessResponse(
        description: 'The pending authorization details',
        content: new JsonContent(ref: AuthorizationConsent::class),
    )]
    #[DefaultResponses([
        HttpResponseCodes::UNAUTHORIZED,
        HttpResponseCodes::NOT_FOUND,
    ])]
    public function __invoke(string $id): Response
    {
        $params = $this->pendingAuthorizationStore->get($id);
        if ($params === null) {
            throw new NotFoundException('authorization', $id);
        }

        $authorizationRequest = $this->authorizationRequestValidator->validate($params);
        if ($authorizationRequest === null) {
            throw new NotFoundException('authorization', $id);
        }

        $user = $this->security->getUser();
        $client = $authorizationRequest->getClient();

        return $this->jsonResponse(new AuthorizationConsent(
            $id,
            new AuthorizationConsentClient($client->getIdentifier(), $client->getName()),
            array_values(array_map(
                static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
                $authorizationRequest->getScopes(),
            )),
            $user instanceof SecurityUser
                ? new AuthorizationConsentUser($user->getId(), $user->getUserIdentifier())
                : null,
        ));
    }
}
