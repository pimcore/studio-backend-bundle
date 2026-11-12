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
use Pimcore\Bundle\StudioBackendBundle\Mercure\Model\TopicCollection;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ClientTokenService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\Loader\TopicLoaderInterface;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\ServerTokenService;
use Pimcore\Bundle\StudioBackendBundle\Mercure\Service\UrlServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\SecurityServiceInterface;
use Pimcore\Model\UserInterface;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;

/**
 * The hub only accepts Mercure 1.0 access tokens: RFC 9068 claims plus Mercure authorization details.
 */
final class TokenServiceTest extends Unit
{
    private const string JWT_KEY = 'a-test-secret-that-is-long-enough-for-hmac-sha256';

    private const string ISSUER = 'https://pimcore.example.com';

    private const string RESOURCE_IDENTIFIER = 'http://mercure/.well-known/mercure';

    private const string PUBLIC_RESOURCE_IDENTIFIER = 'https://pimcore.example.com/.well-known/mercure';

    public function testServerTokenGrantsPublishing(): void
    {
        $service = new ServerTokenService(
            $this->createTopicLoader(new TopicCollection(['studio-backend-default', '*'])),
            new LcobucciFactory(self::JWT_KEY),
            $this->createUrlService(),
            self::ISSUER
        );

        [$header, $claims] = $this->decode($service->getJwt());

        $this->assertSame('at+jwt', $header['typ']);
        $this->assertSame(self::ISSUER, $claims['iss']);
        $this->assertSame(self::RESOURCE_IDENTIFIER, $claims['aud']);
        $this->assertSame('pimcore-studio', $claims['sub']);
        $this->assertSame('pimcore-studio', $claims['client_id']);
        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['publish'],
                'topics' => [['match' => 'studio-backend-default'], ['match' => '*']],
            ],
        ], $claims['authorization_details']);
        $this->assertArrayNotHasKey('mercure', $claims);
    }

    public function testClientTokenGrantsSubscriptionsToTheCurrentUser(): void
    {
        $service = new ClientTokenService(
            $this->createTopicLoader(
                new TopicCollection([], [], [], ['studio-backend-default', 'studio-backend-default/user/2'])
            ),
            new LcobucciFactory(self::JWT_KEY),
            $this->createUrlService(),
            $this->makeEmpty(SecurityServiceInterface::class, [
                'isLoggedIn' => true,
                'getCurrentUser' => $this->makeEmpty(UserInterface::class, ['getId' => 2]),
            ]),
            self::ISSUER
        );

        [$header, $claims] = $this->decode($service->getJwt());

        $this->assertSame('at+jwt', $header['typ']);
        $this->assertSame([self::RESOURCE_IDENTIFIER, self::PUBLIC_RESOURCE_IDENTIFIER], $claims['aud']);
        $this->assertSame('2', $claims['sub']);
        $this->assertSame([
            [
                'type' => 'https://mercure.rocks/authorization-detail',
                'actions' => ['subscribe'],
                'topics' => [['match' => 'studio-backend-default'], ['match' => 'studio-backend-default/user/2']],
            ],
        ], $claims['authorization_details']);
    }

    public function testClientTokenWithoutUserUsesTheClientAsSubject(): void
    {
        $service = new ClientTokenService(
            $this->createTopicLoader(new TopicCollection([], [], [], ['studio-backend-default'])),
            new LcobucciFactory(self::JWT_KEY),
            $this->createUrlService(),
            $this->makeEmpty(SecurityServiceInterface::class, ['isLoggedIn' => false]),
            self::ISSUER
        );

        [, $claims] = $this->decode($service->getJwt());

        $this->assertSame('pimcore-studio', $claims['sub']);
    }

    private function createTopicLoader(TopicCollection $topics): TopicLoaderInterface
    {
        return $this->makeEmpty(TopicLoaderInterface::class, ['loadTopics' => $topics]);
    }

    private function createUrlService(): UrlServiceInterface
    {
        return $this->makeEmpty(UrlServiceInterface::class, [
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
