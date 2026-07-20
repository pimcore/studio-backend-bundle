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
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationRequestValidator;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStore;
use Pimcore\Security\User\User as SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function array_map;
use function array_values;

/**
 * Details of a pending authorization for the Studio UI consent screen: which
 * client is asking, for which scopes, and which user would be acting.
 *
 * @internal
 */
final class AuthorizationDetailsController
{
    public function __construct(
        private readonly PendingAuthorizationStore $pendingAuthorizationStore,
        private readonly AuthorizationRequestValidator $authorizationRequestValidator,
        private readonly Security $security,
    ) {
    }

    #[Route(
        path: '/oauth/authorizations/{id}',
        name: 'pimcore_studio_api_oauth_authorization_details',
        methods: ['GET'],
    )]
    public function __invoke(string $id): JsonResponse
    {
        $params = $this->pendingAuthorizationStore->get($id);
        if ($params === null) {
            return new JsonResponse(['error' => 'unknown_authorization'], Response::HTTP_NOT_FOUND);
        }

        $authorizationRequest = $this->authorizationRequestValidator->validate($params);
        if ($authorizationRequest === null) {
            return new JsonResponse(['error' => 'invalid_authorization'], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->security->getUser();
        $client = $authorizationRequest->getClient();

        return new JsonResponse([
            'authorizationId' => $id,
            'client' => [
                'identifier' => $client->getIdentifier(),
                'name' => $client->getName(),
            ],
            'scopes' => array_values(array_map(
                static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
                $authorizationRequest->getScopes(),
            )),
            'user' => $user instanceof SecurityUser
                ? ['id' => $user->getId(), 'username' => $user->getUserIdentifier()]
                : null,
        ]);
    }
}
