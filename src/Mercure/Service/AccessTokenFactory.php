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

namespace Pimcore\Bundle\StudioBackendBundle\Mercure\Service;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Util\Constant\Mercure;
use function array_map;
use function array_values;
use function bin2hex;
use function random_bytes;
use function time;

/**
 * Built on lcobucci/jwt directly, so it works with every supported symfony/mercure version.
 *
 * @internal
 */
final readonly class AccessTokenFactory implements AccessTokenFactoryInterface
{
    private const string AUTHORIZATION_DETAIL_TYPE = 'https://mercure.rocks/authorization-detail';

    private const string ACTION_PUBLISH = 'publish';

    private const string ACTION_SUBSCRIBE = 'subscribe';

    private Configuration $configuration;

    /**
     * @param non-empty-string $jwtKey
     */
    public function __construct(
        string $jwtKey,
        private string $issuer
    ) {
        $this->configuration = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($jwtKey));
    }

    public function create(
        array $subscribe,
        array $publish,
        string $subject,
        array $audience,
        DateTimeImmutable $expiresAt
    ): string {
        $details = [];
        $legacy = [];
        foreach ([self::ACTION_SUBSCRIBE => $subscribe, self::ACTION_PUBLISH => $publish] as $action => $topics) {
            if ($topics === []) {
                continue;
            }

            $topics = array_values($topics);
            $legacy[$action] = $topics;
            $details[] = [
                'type' => self::AUTHORIZATION_DETAIL_TYPE,
                'actions' => [$action],
                'topics' => array_map(static fn (string $topic): array => ['match' => $topic], $topics),
            ];
        }

        // 1.x hubs refuse any other "typ"; 0.x hubs accept this one
        $builder = $this->configuration->builder()
            ->withHeader('typ', 'at+jwt')
            ->issuedBy($this->issuer)
            ->permittedFor(...$audience)
            ->relatedTo($subject)
            ->withClaim('client_id', Mercure::CLIENT_ID->value)
            // Whole seconds: the hub reads NumericDate claims, lcobucci would otherwise emit fractions
            ->issuedAt(new DateTimeImmutable('@' . time()))
            ->expiresAt(new DateTimeImmutable('@' . $expiresAt->getTimestamp()))
            ->identifiedBy(bin2hex(random_bytes(16)));

        if ($details !== []) {
            $builder = $builder
                ->withClaim('authorization_details', $details)
                ->withClaim('mercure', $legacy);
        }

        return $builder
            ->getToken($this->configuration->signer(), $this->configuration->signingKey())
            ->toString();
    }
}
