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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Element\Service;

use const E_USER_DEPRECATED;
use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\Element\Service\CoauthorService;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidArgumentException;
use Pimcore\Bundle\StudioBackendBundle\Mcp\Context\McpCallContext;
use Pimcore\Model\Version\CoauthorContext;
use function error_reporting;
use function restore_error_handler;
use function set_error_handler;
use function str_repeat;

final class CoauthorServiceTest extends Unit
{
    private McpCallContext $mcpCallContext;

    private CoauthorService $service;

    protected function _before(): void
    {
        $this->mcpCallContext = new McpCallContext();
        $this->service = new CoauthorService(new CoauthorContext(), $this->mcpCallContext);
    }

    public function testOutsideMcpThePayloadCoauthorIsStillExtracted(): void
    {
        $deprecations = $this->collectDeprecations(function (): void {
            $this->assertSame(
                ['type' => 'agent', 'coauthor' => 'x'],
                $this->service->extractFromPayload(['coauthorType' => 'agent', 'coauthor' => 'x'])
            );
        });

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('2027.1', $deprecations[0]);
        $this->assertStringContainsString('platform-version/issues/814', $deprecations[0]);
    }

    public function testPayloadForLaterSavingLosesItsCoauthorInsideAnMcpCall(): void
    {
        $payload = ['key' => 'a', 'coauthorType' => 'agent', 'coauthor' => 'x'];

        $inside = $this->mcpCallContext->run(
            fn (): array => $this->service->withoutPayloadCoauthorInMcpCall($payload)
        );

        $this->assertSame(['key' => 'a'], $inside);
        $this->assertSame($payload, $this->service->withoutPayloadCoauthorInMcpCall($payload));
    }

    public function testOutsideMcpWithoutPayloadCoauthorNoDeprecation(): void
    {
        $deprecations = $this->collectDeprecations(function (): void {
            $this->assertNull($this->service->extractFromPayload(['key' => 'a']));
            $this->assertNull($this->service->extractFromPayload(['coauthorType' => '', 'coauthor' => '']));
        });

        $this->assertSame([], $deprecations);
    }

    public function testInsideMcpTheCoauthorIsDroppedWithoutDeprecation(): void
    {
        $deprecations = $this->collectDeprecations(function (): void {
            $result = $this->mcpCallContext->run(
                fn (): ?array => $this->service->extractFromPayload(['coauthorType' => 'agent', 'coauthor' => 'x'])
            );

            $this->assertNull($result);
        });

        $this->assertSame([], $deprecations);
    }

    public function testInsideMcpAnOverlongPayloadCoauthorIsIgnoredNotRejected(): void
    {
        $result = $this->mcpCallContext->run(
            fn (): ?array => $this->service->extractFromPayload(
                ['coauthorType' => str_repeat('a', 500), 'coauthor' => 'x']
            )
        );

        $this->assertNull($result);
    }

    public function testOutsideMcpAnOverlongPayloadCoauthorIsStillRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->collectDeprecations(function (): void {
            $this->service->extractFromPayload(['coauthorType' => str_repeat('a', 500), 'coauthor' => 'x']);
        });
    }

    /**
     * @return list<string>
     */
    private function collectDeprecations(callable $callback): array
    {
        $messages = [];
        $previousLevel = error_reporting(-1);
        set_error_handler(static function (int $errno, string $message) use (&$messages): bool {
            $messages[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $callback();
        } finally {
            restore_error_handler();
            error_reporting($previousLevel);
        }

        return $messages;
    }
}
