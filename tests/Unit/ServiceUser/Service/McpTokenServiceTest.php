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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Service;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\NotFoundException;
use Pimcore\Bundle\StudioBackendBundle\Security\Service\McpAccessTokenServiceInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Repository\ServiceUserRepositoryInterface;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenService;
use Pimcore\Model\User;
use function time;

/**
 * @internal
 */
final class McpTokenServiceTest extends Unit
{
    public function testIssuesATokenForTheServiceUserWithTheRequestedLifetime(): void
    {
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, [
            'issue' => Expected::once(function (int $userId, int $ttl, string $reference): string {
                $this->assertSame(7, $userId);
                $this->assertSame(30 * 86400, $ttl);
                $this->assertStringStartsWith('service-user:7:', $reference);

                return 'pmcp_value';
            }),
        ]);

        $before = time();
        $token = (new McpTokenService($this->repository($this->serviceUser(active: true)), $tokens))->issueToken(7, 30);

        $this->assertSame('pmcp_value', $token->getToken());
        $this->assertGreaterThanOrEqual($before + 30 * 86400, $token->getExpiresAt());
        $this->assertLessThanOrEqual(time() + 30 * 86400, $token->getExpiresAt());
    }

    public function testEachIssueGetsItsOwnReferenceSoEarlierTokensStayValid(): void
    {
        $references = [];
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, [
            'issue' => static function (int $userId, int $ttl, string $reference) use (&$references): string {
                $references[] = $reference;

                return 'pmcp_value';
            },
        ]);
        $service = new McpTokenService($this->repository($this->serviceUser(active: true)), $tokens);

        $service->issueToken(7, 1);
        $service->issueToken(7, 1);

        $this->assertCount(2, $references);
        $this->assertNotSame($references[0], $references[1]);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function outOfBoundsLifetimes(): iterable
    {
        yield 'zero days' => [0];
        yield 'negative' => [-1];
        yield 'over 90 days' => [91];
    }

    /**
     * @dataProvider outOfBoundsLifetimes
     */
    public function testRefusesALifetimeOutsideOneTo90Days(int $days): void
    {
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, ['issue' => Expected::never()]);

        $this->expectException(InvalidArgumentException::class);
        (new McpTokenService($this->repository($this->serviceUser(active: true)), $tokens))->issueToken(7, $days);
    }

    public function testRefusesAnInactiveServiceUser(): void
    {
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, ['issue' => Expected::never()]);

        $this->expectException(InvalidArgumentException::class);
        (new McpTokenService($this->repository($this->serviceUser(active: false)), $tokens))->issueToken(7, 1);
    }

    public function testAHumanUserGetsNoTokenHere(): void
    {
        $repository = $this->makeEmpty(ServiceUserRepositoryInterface::class, [
            'getServiceUser' => static fn (int $id) => throw new NotFoundException('Service user', $id),
        ]);
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, ['issue' => Expected::never()]);

        $this->expectException(NotFoundException::class);
        (new McpTokenService($repository, $tokens))->issueToken(3, 1);
    }

    public function testRevokesEveryTokenOfTheServiceUser(): void
    {
        $tokens = $this->makeEmpty(McpAccessTokenServiceInterface::class, [
            'revokeByUser' => Expected::once(function (int $userId): void {
                $this->assertSame(7, $userId);
            }),
        ]);

        (new McpTokenService($this->repository($this->serviceUser(active: true)), $tokens))->revokeTokens(7);
    }

    private function repository(User $serviceUser): ServiceUserRepositoryInterface
    {
        return $this->makeEmpty(ServiceUserRepositoryInterface::class, ['getServiceUser' => $serviceUser]);
    }

    private function serviceUser(bool $active): User
    {
        $serviceUser = User::newServiceUser();
        $serviceUser->setId(7);
        $serviceUser->setActive($active);

        return $serviceUser;
    }
}
