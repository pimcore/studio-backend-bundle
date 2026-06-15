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

    public function testRendersAllowedTags(): void
    {
        $this->assertSame('big', $this->generate('{% if value > 5 %}big{% else %}small{% endif %}', ['value' => 10]));
        $this->assertSame('123', $this->generate('{% for i in value %}{{ i }}{% endfor %}', ['value' => [1, 2, 3]]));
        $this->assertSame('10', $this->generate('{% set doubled = value * 2 %}{{ doubled }}', ['value' => 5]));
    }

    public function testRendersStringFilters(): void
    {
        $this->assertSame('ABC', $this->generate('{{ value|upper }}', ['value' => 'abc']));
        $this->assertSame('abc', $this->generate('{{ value|lower }}', ['value' => 'ABC']));
        $this->assertSame('Hello world', $this->generate('{{ value|capitalize }}', ['value' => 'hello world']));
        $this->assertSame('Hello World', $this->generate('{{ value|title }}', ['value' => 'hello world']));
        $this->assertSame('x', $this->generate('{{ value|trim }}', ['value' => '  x  ']));
        $this->assertSame('hi', $this->generate('{{ value|striptags }}', ['value' => '<b>hi</b>']));
        $this->assertSame('4', $this->generate('{{ value|length }}', ['value' => 'abcd']));
        $this->assertSame("a<br />\nb", $this->generate('{{ value|nl2br }}', ['value' => "a\nb"]));
        $this->assertSame('Hello Twig', $this->generate("{{ value|replace({'World': 'Twig'}) }}", ['value' => 'Hello World']));
        $this->assertSame('a-b-c', $this->generate("{{ value|split(',')|join('-') }}", ['value' => 'a,b,c']));
        $this->assertSame('a%20b%26c', $this->generate('{{ value|url_encode }}', ['value' => 'a b&c']));
        $this->assertSame('1-2', $this->generate('{{ value|format(1, 2) }}', ['value' => '%d-%d']));
    }

    public function testRendersArrayFilters(): void
    {
        $this->assertSame('a,b', $this->generate('{{ value|keys|join(",") }}', ['value' => ['a' => 1, 'b' => 2]]));
        $this->assertSame('1,2,3,4', $this->generate('{{ value|merge([4])|join(",") }}', ['value' => [1, 2, 3]]));
        $this->assertSame('3,2,1', $this->generate('{{ value|reverse|join(",") }}', ['value' => [1, 2, 3]]));
        $this->assertSame('1,2,3', $this->generate('{{ value|sort|join(",") }}', ['value' => [3, 1, 2]]));
        $this->assertSame('10', $this->generate('{{ value|first }}', ['value' => [10, 20]]));
        $this->assertSame('20', $this->generate('{{ value|last }}', ['value' => [10, 20]]));
        $this->assertSame('2,3', $this->generate('{{ value|slice(1, 2)|join(",") }}', ['value' => [1, 2, 3, 4]]));
        $this->assertSame('a!,b!', $this->generate("{{ value|map(v => v ~ '!')|join(',') }}", ['value' => ['a', 'b']]));
        $this->assertSame('a,c', $this->generate("{{ value|filter(v => v != 'b')|join(',') }}", ['value' => ['a', 'b', 'c']]));
        $this->assertSame('ab', $this->generate("{{ value|reduce((carry, v) => carry ~ v, '') }}", ['value' => ['a', 'b']]));
        $this->assertSame('b', $this->generate("{{ value|find(v => v == 'b') }}", ['value' => ['a', 'b', 'c']]));
    }

    public function testRendersNumberFilters(): void
    {
        $this->assertSame('7', $this->generate('{{ value|abs }}', ['value' => -7]));
        $this->assertSame('3', $this->generate('{{ value|round }}', ['value' => 2.6]));
        $this->assertSame('2.6', $this->generate("{{ value|round(1, 'floor') }}", ['value' => 2.678]));
        $this->assertSame('1,234.50', $this->generate("{{ value|number_format(2, '.', ',') }}", ['value' => 1234.5]));
    }

    public function testRendersDateFilters(): void
    {
        $this->assertSame('2020-03-15', $this->generate("{{ value|date('Y-m-d') }}", ['value' => '2020-03-15']));
        $this->assertSame(
            '2020-03-16',
            $this->generate("{{ value|date_modify('+1 day')|date('Y-m-d') }}", ['value' => '2020-03-15'])
        );
    }

    public function testRendersEscapingAndEncodingFilters(): void
    {
        $this->assertSame('&lt;b&gt;', $this->generate('{{ value|escape }}', ['value' => '<b>']));
        $this->assertSame('<b>', $this->generate('{{ value|raw }}', ['value' => '<b>']));
        $this->assertSame('[1,2,3]', $this->generate('{{ value|json_encode }}', ['value' => [1, 2, 3]]));
        // raw is required to emit unescaped JSON when auto-escaping is enabled.
        $this->assertSame('{"a":1}', $this->generate('{{ value|json_encode|raw }}', ['value' => ['a' => 1]]));
        $this->assertSame('fallback', $this->generate("{{ value|default('fallback') }}", ['value' => null]));
        $this->assertSame('present', $this->generate("{{ value|default('fallback') }}", ['value' => 'present']));
    }

    public function testRendersShuffleFilter(): void
    {
        // shuffle is non-deterministic; sorting afterwards makes the assertion stable while still
        // exercising the filter.
        $this->assertSame('1,2,3', $this->generate('{{ value|shuffle|sort|join(",") }}', ['value' => [3, 1, 2]]));
    }

    /**
     * String filters require twig/string-extra to be installed and registered.
     */
    public function testRendersStringExtraFiltersWhenAvailable(): void
    {
        if (!class_exists(StringExtension::class)) {
            $this->markTestSkipped('twig/string-extra is not installed.');
        }

        $this->assertSame('car', $this->generate('{{ value|singular }}', ['value' => 'cars']));
        $this->assertSame('cars', $this->generate('{{ value|plural }}', ['value' => 'car']));
    }

    /**
     * Localization filters (issue #3450) require twig/intl-extra to be installed and registered.
     */
    public function testRendersIntlFiltersWhenAvailable(): void
    {
        if (!class_exists(IntlExtension::class)) {
            $this->markTestSkipped('twig/intl-extra is not installed.');
        }

        $this->assertStringContainsString('234', $this->generate("{{ value|format_number(locale='en') }}", ['value' => 1234.5]));

        $currency = $this->generate("{{ value|format_currency('EUR', locale='en') }}", ['value' => 1234.5]);
        $this->assertStringContainsString('234', $currency);
        $this->assertStringContainsString('€', $currency);

        $this->assertSame('United States', $this->generate("{{ value|country_name('en') }}", ['value' => 'US']));
        $this->assertSame('Euro', $this->generate("{{ value|currency_name('en') }}", ['value' => 'EUR']));
        $this->assertSame('€', $this->generate("{{ value|currency_symbol('en') }}", ['value' => 'EUR']));
        $this->assertSame('English', $this->generate("{{ value|language_name('en') }}", ['value' => 'en']));
        $this->assertSame('English', $this->generate("{{ value|locale_name('en') }}", ['value' => 'en']));
        $this->assertStringContainsString(
            '2020',
            $this->generate("{{ value|format_date('long', timezone='UTC', locale='en') }}", ['value' => '2020-03-15'])
        );
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

    public function testBlocksDisallowedTag(): void
    {
        // The "apply" tag is not part of the allowed tags and must be rejected.
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{% apply upper %}{{ value }}{% endapply %}', ['value' => 'x']);
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
