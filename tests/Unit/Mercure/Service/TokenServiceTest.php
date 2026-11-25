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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Mercure\Service;

use Codeception\Test\Unit;
use DateTimeImmutable;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\AccessTokenFactory;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\AudienceServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ClientTokenService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ServerTokenService;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;

/**
 * Tokens serve both hub generations: Mercure 1.0 access token claims (RFC 9068 plus authorization
 * details) for 1.x hubs, the "mercure" claim for 0.x hubs. Each hub ignores the other's claims.
 */
final class TokenServiceTest extends Unit
{
    private const string JWT_KEY = 'a-test-secret-that-is-long-enough-for-hmac-sha256';

    private const string ISSUER = 'https://pimcore.example.com';

    private const string RESOURCE_IDENTIFIER = 'http://mercure/.well-known/mercure';

    private const string PUBLIC_RESOURCE_IDENTIFIER = 'https://pimcore.example.com/.well-known/mercure';

    public function testServerTokenGrantsPublishingOnBothProtocols(): void
    {
        $service = new ServerTokenService(
            $this->createTopicLoader(new TopicCollection(['studio-backend-default', '*'])),
            new AccessTokenFactory(self::JWT_KEY, self::ISSUER),
            $this->createAudienceService()
        );

        [$header, $claims] = $this->decode($service->getJwt());

        $this->assertSame('at+jwt', $header['typ']);
        $this->assertSame('HS256', $header['alg']);
        $this->assertSame(self::ISSUER, $claims['iss']);
        $this->assertSame(self::RESOURCE_IDENTIFIER, $claims['aud']);
        $this->assertSame('pimcore-studio', $claims['sub']);
        $this->assertSame('pimcore-studio', $claims['client_id']);
        $this->assertIsInt($claims['iat']);
        $this->assertIsInt($claims['exp']);
        $this->assertEqualsWithDelta(time() + 3600, $claims['exp'], 5);
        $this->assertNotEmpty($claims['jti']);
        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['publish'],
                'topics' => [['match' => 'studio-backend-default'], ['match' => '*']],
            ],
        ], $claims['authorization_details']);
        $this->assertSame(['publish' => ['studio-backend-default', '*']], $claims['mercure']);
    }

    public function testClientTokenGrantsSubscriptionsToTheCurrentUserOnBothProtocols(): void
    {
        $service = new ClientTokenService(
            $this->createTopicLoader(
                new TopicCollection([], [], [], ['studio-backend-default', 'studio-backend-default/user/2'])
            ),
            new AccessTokenFactory(self::JWT_KEY, self::ISSUER),
            $this->createAudienceService(),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'isLoggedIn' => true,
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => 2]),
            ]),
            900
        );

        [$header, $claims] = $this->decode($service->getJwt());

        $this->assertSame('at+jwt', $header['typ']);
        $this->assertSame([self::RESOURCE_IDENTIFIER, self::PUBLIC_RESOURCE_IDENTIFIER], $claims['aud']);
        $this->assertSame('2', $claims['sub']);
        $this->assertEqualsWithDelta(time() + 900, $claims['exp'], 5);
        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe'],
                'topics' => [['match' => 'studio-backend-default'], ['match' => 'studio-backend-default/user/2']],
            ],
        ], $claims['authorization_details']);
        $this->assertSame(
            ['subscribe' => ['studio-backend-default', 'studio-backend-default/user/2']],
            $claims['mercure']
        );
    }

    public function testClientTokenWithoutUserUsesTheClientAsSubject(): void
    {
        $service = new ClientTokenService(
            $this->createTopicLoader(new TopicCollection([], [], [], ['studio-backend-default'])),
            new AccessTokenFactory(self::JWT_KEY, self::ISSUER),
            $this->createAudienceService(),
            $this->makeEmpty(SecurityServiceInterface::class, ['isLoggedIn' => false])
        );

        [, $claims] = $this->decode($service->getJwt());

        $this->assertSame('pimcore-studio', $claims['sub']);
    }

    public function testTokenWithoutTopicsCarriesNoGrants(): void
    {
        $jwt = (new AccessTokenFactory(self::JWT_KEY, self::ISSUER))->create(
            [],
            [],
            'pimcore-studio',
            [self::RESOURCE_IDENTIFIER],
            new DateTimeImmutable('+60 seconds')
        );

        [, $claims] = $this->decode($jwt);

        $this->assertArrayNotHasKey('authorization_details', $claims);
        $this->assertArrayNotHasKey('mercure', $claims);
    }

    public function testTokenIsSignedWithTheJwtKey(): void
    {
        $jwt = (new AccessTokenFactory(self::JWT_KEY, self::ISSUER))->create(
            ['studio-backend-default'],
            [],
            'pimcore-studio',
            [self::RESOURCE_IDENTIFIER],
            new DateTimeImmutable('+60 seconds')
        );

        [$header, $payload, $signature] = explode('.', $jwt);
        $expected = rtrim(strtr(base64_encode(
            hash_hmac('sha256', $header . '.' . $payload, self::JWT_KEY, true)
        ), '+/', '-_'), '=');

        $this->assertSame($expected, $signature);
    }

    private function createTopicLoader(TopicCollection $topics): TopicLoaderInterface
    {
        return $this->makeEmpty(TopicLoaderInterface::class, ['loadTopics' => $topics]);
    }

    private function createAudienceService(): AudienceServiceInterface
    {
        return $this->makeEmpty(AudienceServiceInterface::class, [
            'getResourceIdentifier' => self::RESOURCE_IDENTIFIER,
            'getSubscriberAudience' => [self::RESOURCE_IDENTIFIER, self::PUBLIC_RESOURCE_IDENTIFIER],
        ]);
    }

    /**
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function decode(string $jwt): array
    {
        [$header, $payload] = explode('.', $jwt);

        return [
            json_decode(base64_decode(strtr($header, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR),
            json_decode(base64_decode(strtr($payload, '-_', '+/')), true, 512, JSON_THROW_ON_ERROR),
        ];
    }
}
