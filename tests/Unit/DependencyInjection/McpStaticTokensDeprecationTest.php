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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\DependencyInjection;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use ReflectionMethod;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;

/**
 * Static MCP personal access tokens in YAML are deprecated in 2026.4 and removed in 2027.1.
 * Configuring them must say so at container build, not only in the docs.
 *
 * @internal
 */
final class McpStaticTokensDeprecationTest extends Unit
{
    private const string DEPRECATION = 'Static MCP personal access tokens are deprecated';

    public function testConfiguringStaticTokensRaisesADeprecation(): void
    {
        $messages = $this->collectDeprecations(fn () => $this->process([
            'mcp' => ['authentication' => ['tokens' => ['agent-user' => ['static-token']]]],
        ]));

        $this->assertCount(1, $messages);
        $this->assertStringContainsString(self::DEPRECATION, $messages[0]);
        $this->assertStringContainsString('2027.1', $messages[0]);
        $this->assertStringContainsString('OAuth', $messages[0]);
    }

    public function testNotConfiguringStaticTokensRaisesNoDeprecation(): void
    {
        $messages = $this->collectDeprecations(fn () => $this->process([]));

        $this->assertSame([], $messages);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        $root = (new TreeBuilder('pimcore_studio_backend'))->getRootNode();
        (new ReflectionMethod(Configuration::class, 'addMcpNode'))
            ->invoke(new Configuration(), $root);

        return (new Processor())->process($root->getNode(true), [$config]);
    }

    /**
     * @return list<string> messages of every E_USER_DEPRECATED raised while $callback runs
     */
    private function collectDeprecations(callable $callback): array
    {
        $messages = [];
        set_error_handler(
            static function (int $type, string $message) use (&$messages): bool {
                if ($type === E_USER_DEPRECATED) {
                    $messages[] = $message;
                }

                return true;
            }
        );

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
