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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\ServiceUser\Command;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Command\McpTokenCommand;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Schema\ServiceUserMcpToken;
use Pimcore\Bundle\StudioBackendBundle\ServiceUser\Service\McpTokenServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 */
final class McpTokenCommandTest extends Unit
{
    public function testIssuesATokenAndPrintsItOnce(): void
    {
        $service = $this->makeEmpty(McpTokenServiceInterface::class, [
            'issueToken' => Expected::once(function (int $id, int $days): ServiceUserMcpToken {
                $this->assertSame(42, $id);
                $this->assertSame(7, $days);

                return new ServiceUserMcpToken('pmcp_value', 1900000000);
            }),
            'revokeTokens' => Expected::never(),
        ]);

        $tester = new CommandTester(new McpTokenCommand($service));
        $exitCode = $tester->execute(['id' => '42', '--ttl-days' => '7']);

        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertSame(1, substr_count($tester->getDisplay(), 'pmcp_value'));
    }

    public function testDefaultsTo30Days(): void
    {
        $service = $this->makeEmpty(McpTokenServiceInterface::class, [
            'issueToken' => Expected::once(function (int $id, int $days): ServiceUserMcpToken {
                $this->assertSame(30, $days);

                return new ServiceUserMcpToken('pmcp_value', 1900000000);
            }),
        ]);

        (new CommandTester(new McpTokenCommand($service)))->execute(['id' => '42']);
    }

    public function testRevokesAllTokensOfTheServiceUser(): void
    {
        $service = $this->makeEmpty(McpTokenServiceInterface::class, [
            'issueToken' => Expected::never(),
            'revokeTokens' => Expected::once(function (int $id): void {
                $this->assertSame(42, $id);
            }),
        ]);

        $tester = new CommandTester(new McpTokenCommand($service));

        $this->assertSame(Command::SUCCESS, $tester->execute(['id' => '42', '--revoke' => true]));
    }

    public function testAnOutOfBoundsLifetimeFails(): void
    {
        $service = $this->makeEmpty(McpTokenServiceInterface::class, [
            'issueToken' => static fn () => throw new InvalidArgumentException(
                'The token lifetime must be between 1 and 90 days.'
            ),
        ]);

        $tester = new CommandTester(new McpTokenCommand($service));

        $this->assertSame(Command::FAILURE, $tester->execute(['id' => '42', '--ttl-days' => '365']));
        $this->assertStringContainsString('between 1 and 90 days', $tester->getDisplay());
    }

    public function testANonNumericIdFails(): void
    {
        $service = $this->makeEmpty(McpTokenServiceInterface::class, ['issueToken' => Expected::never()]);

        $tester = new CommandTester(new McpTokenCommand($service));

        $this->assertSame(Command::INVALID, $tester->execute(['id' => 'svc-erp-sync']));
    }
}
