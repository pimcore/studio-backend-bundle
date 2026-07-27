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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Token;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\RegisteredClaims;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\IdentityResolverInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenRevocationCheckerInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Contract\TokenValidatorInterface;
use Pimcore\Bundle\StudioBackendBundle\OAuth\Dto\ResolvedAccess;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\NativeClock;
use Throwable;
use function array_filter;
use function array_values;
use function is_array;
use function is_string;
use function preg_split;
use function str_contains;
use function trim;
use const PREG_SPLIT_NO_EMPTY;

/**
 * Validates a JWT access token minted by the embedded authorization server:
 * verifies the signature against the configured public key, checks expiry and
 * (when configured) the issuer, rejects revoked tokens, and resolves the
 * subject to a Pimcore user.
 *
 * Uses lcobucci/jwt directly rather than league's resource-server middleware,
 * so it plugs into the firewall authenticator without pulling in PSR-7 plumbing.
 *
 * @internal
 */
final class EmbeddedTokenValidator implements TokenValidatorInterface
{
    private readonly ClockInterface $clock;

    private ?Configuration $configuration = null;

    public function __construct(
        private readonly ?string $publicKey,
        private readonly ?string $issuer,
        private readonly IdentityResolverInterface $identityResolver,
        private readonly TokenRevocationCheckerInterface $revocationChecker,
        ?ClockInterface $clock = null,
    ) {
        $this->clock = $clock ?? new NativeClock();
    }

    public function validate(string $rawToken, string $resourceUri): ?ResolvedAccess
    {
        $configuration = $this->configuration();
        if ($configuration === null) {
            return null;
        }

        $token = $this->parseVerifiedToken($configuration, $rawToken);
        if ($token === null) {
            return null;
        }

        $claims = $token->claims();

        // Fail closed: LooseValidAt only checks time claims that are present, so
        // a token without an expiry would otherwise never expire.
        if (!$claims->has(RegisteredClaims::EXPIRATION_TIME)) {
            return null;
        }

        $tokenId = $claims->get('jti');
        if (is_string($tokenId) && $this->revocationChecker->isRevoked($tokenId)) {
            return null;
        }

        $audience = $this->toStringList($claims->get('aud', []));
        if (!$this->isAudienceAllowed($audience, $resourceUri)) {
            return null;
        }

        $subject = $claims->get('sub');
        if (!is_string($subject)) {
            return null;
        }

        $user = $this->identityResolver->resolve($subject);
        if ($user === null) {
            return null;
        }

        $clientId = $claims->get('client_id', '');

        return new ResolvedAccess(
            $user,
            $this->parseScopes($claims->get('scope', '')),
            $audience,
            is_string($clientId) ? $clientId : '',
        );
    }

    private function parseVerifiedToken(Configuration $configuration, string $rawToken): ?UnencryptedToken
    {
        try {
            $token = $configuration->parser()->parse($rawToken);

            $constraints = [
                new SignedWith($configuration->signer(), $configuration->verificationKey()),
                new LooseValidAt($this->clock),
            ];
            if ($this->issuer !== null) {
                $constraints[] = new IssuedBy($this->issuer);
            }

            $configuration->validator()->assert($token, ...$constraints);
        } catch (Throwable) {
            return null;
        }

        return $token instanceof UnencryptedToken ? $token : null;
    }

    /**
     * Endpoint-vs-audience check. Currently accepts any audience; resource
     * binding is enforced additively later, so the parameters are retained as
     * the seam for that check.
     *
     * @param list<string> $audience
     */
    private function isAudienceAllowed(array $audience, string $resourceUri): bool
    {
        return true;
    }

    private function configuration(): ?Configuration
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }
        if ($this->publicKey === null) {
            return null;
        }

        $key = str_contains($this->publicKey, 'BEGIN')
            ? InMemory::plainText($this->publicKey)
            : InMemory::file($this->publicKey);

        // Verification only: the signer/verification key are used to check the
        // signature; the same public key fills the (unused) signing-key slot.
        $this->configuration = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);

        return $this->configuration;
    }

    /**
     * @return list<string>
     */
    private function parseScopes(mixed $scope): array
    {
        if (is_array($scope)) {
            return array_values(array_filter($scope, is_string(...)));
        }

        if (!is_string($scope) || $scope === '') {
            return [];
        }

        // Split on any run of whitespace so double spaces don't yield empty scopes.
        $scopes = preg_split('/\s+/u', trim($scope), -1, PREG_SPLIT_NO_EMPTY);

        return $scopes === false ? [] : $scopes;
    }

    /**
     * @return list<string>
     */
    private function toStringList(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }
}
