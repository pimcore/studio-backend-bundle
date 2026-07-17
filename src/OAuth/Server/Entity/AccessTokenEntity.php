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

namespace Pimcore\Bundle\StudioBackendBundle\OAuth\Server\Entity;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use function array_map;
use function implode;

/**
 * JWT access token. Replaces league's default AccessTokenTrait so the token
 * carries RFC 9068 claims — a space-delimited `scope` string, `client_id`, and
 * `iss` — instead of league's `aud`=client-id / `scopes` array. Resource
 * audience binding is added later.
 *
 * @internal
 */
final class AccessTokenEntity implements AccessTokenEntityInterface
{
    use EntityTrait;
    use TokenEntityTrait;

    private CryptKeyInterface $privateKey;

    private ?string $issuer = null;

    public function setPrivateKey(CryptKeyInterface $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    public function setIssuer(?string $issuer): void
    {
        $this->issuer = $issuer;
    }

    public function toString(): string
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privateKey->getKeyContents(), $this->privateKey->getPassPhrase() ?? ''),
            InMemory::plainText('empty', 'empty'),
        );

        $now = new DateTimeImmutable();
        $builder = $configuration->builder()
            ->identifiedBy($this->getIdentifier())
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo($this->getSubjectIdentifier())
            ->withClaim('scope', $this->getScopeString())
            ->withClaim('client_id', $this->getClient()->getIdentifier());

        if ($this->issuer !== null) {
            $builder = $builder->issuedBy($this->issuer);
        }

        return $builder->getToken($configuration->signer(), $configuration->signingKey())->toString();
    }

    private function getSubjectIdentifier(): string
    {
        return $this->getUserIdentifier() ?? $this->getClient()->getIdentifier();
    }

    private function getScopeString(): string
    {
        return implode(' ', array_map(
            static fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(),
            $this->getScopes(),
        ));
    }
}
