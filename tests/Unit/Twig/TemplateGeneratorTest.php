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

namespace Pimcore\Bundle\StudioBackendBundle\Tests\Unit\Twig;

use Codeception\Test\Unit;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidTemplateException;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializer;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGenerator;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use ReflectionMethod;
use Symfony\Component\Config\Definition\Processor;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;

/**
 * @internal
 */
final class TemplateGeneratorTest extends Unit
{
    public function testRendersDefaultTemplate(): void
    {
        $this->assertSame('5', $this->generate('{{ value }}', ['value' => 5]));
    }

    /**
     * Filters newly added to the default sandbox whitelist (issue #3450) must be usable.
     */
    public function testRendersNewlyAllowedCoreFilters(): void
    {
        $this->assertSame('5', $this->generate('{{ value|abs }}', ['value' => -5]));
        $this->assertSame('fallback', $this->generate("{{ value|default('fallback') }}", ['value' => null]));
        $this->assertSame('[1,2,3]', $this->generate('{{ value|json_encode }}', ['value' => [1, 2, 3]]));
        $this->assertSame('abc', $this->generate("{{ value|slice(0, 3) }}", ['value' => 'abcdef']));
        $this->assertSame('a-b-c', $this->generate("{{ value|join('-') }}", ['value' => ['a', 'b', 'c']]));
    }

    /**
     * The sandbox must keep rejecting anything outside the whitelist, even after the additions.
     */
    public function testBlocksUnknownFilter(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ value|nonexistent_filter }}', ['value' => 'x']);
    }

    public function testBlocksNonWhitelistedFunction(): void
    {
        // The "source" function can read arbitrary files; it must never be reachable from the sandbox.
        $this->expectException(InvalidTemplateException::class);
        $this->generate("{{ source('LICENSE.md') }}", []);
    }

    public function testBlocksConstantFunction(): void
    {
        // "constant" is a classic information-disclosure vector and must stay blocked.
        $this->expectException(InvalidTemplateException::class);
        $this->generate("{{ constant('PHP_VERSION') }}", []);
    }

    /**
     * Intl filters (issue #3450) only work when twig/intl-extra is installed and registered.
     */
    public function testRendersIntlFilterWhenAvailable(): void
    {
        if (!class_exists(IntlExtension::class)) {
            $this->markTestSkipped('twig/intl-extra is not installed.');
        }

        $rendered = $this->generate("{{ value|format_number }}", ['value' => 1234.5]);
        $this->assertStringContainsString('234', $rendered);
    }

    private function generate(string $template, array $context): string
    {
        $twig = new Environment(new ArrayLoader());
        // The initializer expects the SandboxExtension to be present on the environment.
        $twig->addExtension(new SandboxExtension(new SecurityPolicy()));

        if (class_exists(StringExtension::class)) {
            $twig->addExtension(new StringExtension());
        }
        if (class_exists(IntlExtension::class)) {
            $twig->addExtension(new IntlExtension());
        }

        $policy = $this->getDefaultSandboxPolicy();

        $generator = new TemplateGenerator(
            $twig,
            new SandboxExtensionInitializer(
                $twig,
                $policy['tags'],
                $policy['filters'],
                $policy['functions']
            )
        );

        return $generator->generate($template, $context);
    }

    /**
     * Reads the real default whitelist from the bundle Configuration so the test tracks any
     * future changes to the sandbox policy instead of duplicating the list.
     *
     * @return array{tags: list<string>, filters: list<string>, functions: list<string>}
     */
    private function getDefaultSandboxPolicy(): array
    {
        $method = new ReflectionMethod(Configuration::class, 'addTwigSandboxNode');
        $method->setAccessible(true);
        $node = $method->invoke(new Configuration())->getNode(true);

        $processed = (new Processor())->process($node, []);

        return $processed['sandbox_security_policy'];
    }
}
