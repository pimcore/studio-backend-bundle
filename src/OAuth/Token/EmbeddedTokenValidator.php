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
use function explode;
use function is_array;
use function is_string;
use function str_contains;

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

        if (!$token instanceof UnencryptedToken) {
            return null;
        }

        $claims = $token->claims();

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

    /**
     * Endpoint-vs-audience check. Currently accepts any audience; resource
     * binding is enforced additively later.
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
        return $this->configuration = Configuration::forAsymmetricSigner(new Sha256(), $key, $key);
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

        return explode(' ', $scope);
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
