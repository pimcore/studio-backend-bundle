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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Service;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\UserNotFoundException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Event\AuthorizationConsentEvent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Exception\MissingKeyMaterialException;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Hydrator\AuthorizationConsentHydratorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationConsent;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Schema\AuthorizationRedirect;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationRequestValidatorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\AuthorizationServerFactoryInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity\UserEntity;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Server\PendingAuthorizationStoreInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use function array_map;
use function implode;
use function rawurlencode;
use function str_contains;

/**
 * Orchestrates the consent handoff: reading a pending authorization back for the screen,
 * and completing it once the user has decided.
 *
 * @internal
 */
final readonly class AuthorizationConsentService implements AuthorizationConsentServiceInterface
{
    private const string RESOURCE_NAME = 'authorization';

    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private PendingAuthorizationStoreInterface $pendingAuthorizationStore,
        private AuthorizationRequestValidatorInterface $authorizationRequestValidator,
        private AuthorizationServerFactoryInterface $authorizationServerFactory,
        private AuthorizationConsentHydratorInterface $consentHydrator,
        private ResponseFactoryInterface $psrResponseFactory,
        private SecurityServiceInterface $securityService,
        private ?string $issuer = null,
    ) {
    }

    /**
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    public function getConsent(string $authorizationId): AuthorizationConsent
    {
        $consent = $this->consentHydrator->hydrate(
            $authorizationId,
            // Read, not claimed: looking at the consent screen must not end the
            // authorization, or a reload would lose it.
            $this->validated($authorizationId, $this->pendingAuthorizationStore->get($authorizationId)),
            $this->currentUser(),
        );

        $this->eventDispatcher->dispatch(
            new AuthorizationConsentEvent($consent),
            AuthorizationConsentEvent::EVENT_NAME,
        );

        return $consent;
    }

    /**
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    public function completeConsent(string $authorizationId, bool $approved): AuthorizationRedirect
    {
        $user = $this->currentUser();
        if ($user === null || $user->getId() === null) {
            // The decision is recorded against a user, so an unidentifiable one cannot be
            // acting. Reported as a missing authorization rather than as an auth error: a
            // pending authorization is only ever the subject's own.
            throw new NotFoundException(self::RESOURCE_NAME, $authorizationId);
        }

        // Claimed before anything is minted, and this order is the point: consume() hands
        // the parameters to exactly one caller, so a second approval of the same id arrives
        // to nothing and is refused right here. Reading first and deleting afterwards - which
        // is what this did - let two concurrent approvals each reach league with a valid
        // request and walk away with a code apiece.
        $authorizationRequest = $this->validated(
            $authorizationId,
            $this->pendingAuthorizationStore->consume($authorizationId),
        );
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

        // No pre-response event here, deliberately, and it is the one place in this bundle
        // where that is the right call. This value is the location the browser is sent to
        // carrying the authorization code, and league has just validated it against the
        // client's registered redirect URIs. A listener able to rewrite it would undo that
        // check after the fact and could take delivery of the code, which is the single
        // control the authorization-code flow rests on. The consent payload is a read model
        // and does get an event; this is a security decision's output and does not.
        return new AuthorizationRedirect($this->withIssuer($psrResponse->getHeaderLine('Location')));
    }

    public function pinConsentParameters(
        array $queryParams,
        AuthorizationRequestInterface $authorizationRequest,
    ): array {
        $scopes = array_map(
            static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $authorizationRequest->getScopes(),
        );

        if ($scopes !== []) {
            $queryParams['scope'] = implode(' ', $scopes);
        }

        return $queryParams;
    }

    /**
     * @param array<string, mixed>|null $params
     *
     * @throws MissingKeyMaterialException
     * @throws NotFoundException
     */
    private function validated(string $authorizationId, ?array $params): AuthorizationRequestInterface
    {
        if ($params === null) {
            throw new NotFoundException(self::RESOURCE_NAME, $authorizationId);
        }

        // Re-validated rather than unserialized, so the screen and the approval both act on
        // a request that is still valid now, not one that was valid when it was stashed.
        $authorizationRequest = $this->authorizationRequestValidator->validate($params);
        if ($authorizationRequest === null) {
            throw new NotFoundException(self::RESOURCE_NAME, $authorizationId);
        }

        return $authorizationRequest;
    }

    private function currentUser(): ?UserInterface
    {
        try {
            return $this->securityService->getCurrentUser();
        } catch (UserNotFoundException) {
            return null;
        }
    }

    /**
     * RFC 9207: identify the issuer in the authorization response. The same configured value
     * the metadata endpoint advertises and the token carries, so a client comparing the
     * three sees one identity.
     */
    private function withIssuer(string $location): string
    {
        if ($location === '' || $this->issuer === null) {
            return $location;
        }

        $separator = str_contains($location, '?') ? '&' : '?';

        return $location . $separator . 'iss=' . rawurlencode($this->issuer);
    }
}
