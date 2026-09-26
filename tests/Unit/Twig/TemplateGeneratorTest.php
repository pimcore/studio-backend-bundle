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
use DateTime;
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\Configuration;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidTemplateException;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializer;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGenerator;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Psr\Log\AbstractLogger;
use ReflectionMethod;
use ReflectionNamedType;
use Stringable;
use Symfony\Component\Config\Definition\Processor;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;
use function array_merge;
use function sprintf;
use function strtoupper;

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
        // shuffle is non-deterministic; sorting afterwards makes the assertion stable while
        // still exercising the filter.
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

    public function testBlocksIncludeTag(): void
    {
        // "include" would let a template pull in an arbitrary sibling template; it is not
        // part of the allowed tags and must be rejected.
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{% include "unknown.twig" %}', []);
    }

    /**
     * The isolated environment registers no Pimcore Twig extension, so "pimcore_object" does
     * not exist for it to resolve - the element loader is unreachable, not merely sandboxed.
     * This is the exploit from the original report: an open policy let this call delete an
     * object straight from a grid's advanced column template.
     */
    public function testBlocksElementDeleteViaPimcoreFunction(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ pimcore_object(1).delete() }}', []);
    }

    /**
     * @dataProvider pimcoreFunctionProvider
     */
    public function testBlocksPimcoreServiceFunctions(string $call): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ ' . $call . ' }}', []);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public function pimcoreFunctionProvider(): iterable
    {
        yield 'pimcore_object' => ['pimcore_object(1)'];
        yield 'pimcore_object_by_path' => ['pimcore_object_by_path("/foo")'];
        yield 'pimcore_asset' => ['pimcore_asset(1)'];
        yield 'pimcore_document' => ['pimcore_document(1)'];
        yield 'pimcore_user' => ['pimcore_user(1)'];
    }

    /**
     * A value reaching the template unblocked (e.g. a DateTime for a date column) must stay
     * read-only: mutating methods are hard-blocked regardless of the class denylist.
     */
    public function testBlocksSetterCallOnUnblockedValue(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate("{{ value.modify('+1 day') }}", ['value' => new DateTime('2020-01-01')]);
    }

    /**
     * A Pimcore element reaching the template (a bug, not something that should legitimately
     * happen - {@see \Pimcore\Bundle\StudioBackendBundle\Grid\Column\Transformer\TwigOperator}
     * sanitizes every value before it gets here) must still have every method call denied.
     * The mock's delete() itself fails the test if invoked, so this also proves the policy
     * rejects the call before it ever reaches the object - not merely that some exception
     * bubbles up afterward.
     */
    public function testBlocksMethodCallOnElementInterfaceValue(): void
    {
        $element = $this->makeEmpty(ElementInterface::class, [
            'delete' => function (): void {
                self::fail('delete() must never be invoked from a sandboxed template.');
            },
        ]);

        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ value.delete() }}', ['value' => $element]);
    }

    public function testRangeFunctionWithinTheCapWorks(): void
    {
        $this->assertSame('1,2,3,4,5', $this->generate('{{ range(1, 5)|join(",") }}', []));
    }

    public function testRangeFunctionAtExactlyTheCapWorks(): void
    {
        $this->assertSame('1000', $this->generate('{{ range(1, 1000)|length }}', []));
    }

    /**
     * range() maps directly onto PHP's own range(): an uncapped call like range(0, 1000000)
     * would allocate a huge array straight from template text - a memory/CPU DoS reachable
     * with no object or method call involved at all.
     */
    public function testRangeFunctionRejectsASpanBeyondTheCap(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ range(0, 1000000)|length }}', []);
    }

    /**
     * A character range is inherently bounded (at most the codepoint distance between the two
     * characters) and must keep working uncapped.
     */
    public function testRangeFunctionStillSupportsCharacterRanges(): void
    {
        $this->assertSame('a,b,c,d,e', $this->generate('{{ range("a", "e")|join(",") }}', []));
    }

    /**
     * The constructor must never accept a Twig Environment at all - the isolated environment
     * is always built from scratch inside SandboxExtensionInitializer (see its class
     * docblock). A structural guard against regressing to the old design, where an injected
     * shared `twig` service's SandboxExtension had its policy replaced in place.
     */
    public function testInitializerConstructorNeverAcceptsATwigEnvironment(): void
    {
        $constructor = new ReflectionMethod(SandboxExtensionInitializer::class, '__construct');

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            $isEnvironment = $type instanceof ReflectionNamedType && $type->getName() === Environment::class;

            $this->assertFalse(
                $isEnvironment,
                sprintf('Constructor parameter "$%s" must not accept a Twig Environment.', $parameter->getName())
            );
        }
    }

    /**
     * Rendering a TwigOperator template must never touch an external SandboxExtension's
     * policy - previously this called setSecurityPolicy() on the application's shared
     * SandboxExtension, permanently weakening the policy core uses for its own Twig rendering
     * (Mailer, the "Text" layout component, ...) for the remainder of the process. Unlike the
     * original version of this test, $externalTwig below is not a disconnected fixture:
     * getEnvironment() is asserted to be a distinct instance, so this fails if
     * TemplateGenerator/SandboxExtensionInitializer is ever changed to reuse an
     * externally-supplied environment instead of building its own.
     */
    public function testGeneratorNeverTouchesAnExternalSandboxExtension(): void
    {
        $externalPolicy = new SecurityPolicy(['set'], ['escape', 'trans', 'default'], ['path', 'asset']);
        $externalSandbox = new SandboxExtension($externalPolicy);
        $externalTwig = new Environment(new ArrayLoader());
        $externalTwig->addExtension($externalSandbox);

        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer($policy['tags'], $policy['filters'], $policy['functions']);
        $generator = new TemplateGenerator($initializer);

        $rendered = $generator->generate('{{ value|upper }}', ['value' => 'twig-operator-render']);
        $this->assertSame('TWIG-OPERATOR-RENDER', $rendered);

        try {
            $generator->generate('{{ pimcore_object(1).delete() }}', []);
        } catch (InvalidTemplateException) {
            // Expected - the attempted exploit itself must not have side effects either.
        }

        $this->assertNotSame(
            $externalTwig,
            $initializer->getEnvironment(),
            'The isolated environment must never be the externally-supplied one.'
        );
        $this->assertSame(
            $externalPolicy,
            $externalSandbox->getSecurityPolicy(),
            "Rendering a TwigOperator template must not touch an external SandboxExtension's policy."
        );
    }

    /**
     * The DI extension point ({@see SandboxExtensionInitializerInterface::TWIG_OPERATOR_EXTENSION_TAG})
     * is the supported way to add a project-defined filter/function/tag: the isolated
     * environment never sees the application's shared `twig` service (see finding 2 of the
     * review this test accompanies), so a tagged extension is registered directly onto it.
     */
    public function testAdditionalTaggedExtensionIsRegisteredIntoTheIsolatedEnvironment(): void
    {
        $extension = new class extends AbstractExtension {
            public function getFunctions(): array
            {
                return [
                    new TwigFunction('project_shout', static fn (string $value): string => strtoupper($value) . '!!!'),
                ];
            }
        };

        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['project_shout']),
            [$extension]
        );
        $generator = new TemplateGenerator($initializer);

        $this->assertSame('HI!!!', $generator->generate('{{ project_shout(value) }}', ['value' => 'hi']));
    }

    /**
     * A `sandbox_security_policy` allow-list entry only takes effect if a Twig extension in
     * the isolated environment actually registers it (see the docblock on
     * warnAboutUnregisteredAllowListNames()). Misconfiguring it - adding a name nothing
     * registers - must be surfaced, not fail silently until a template happens to use it.
     */
    public function testWarnsOnceWhenAnAllowListedFunctionIsNeverRegistered(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{0: string, 1: string, 2: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message, $context];
            }
        };

        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['does_not_exist_anywhere']),
            [],
            $logger
        );

        // build() memoizes; calling twice must still log only once.
        $initializer->getEnvironment();
        $initializer->getEnvironment();

        $this->assertCount(1, $logger->records, 'The warning must be logged exactly once.');
        $this->assertSame('warning', $logger->records[0][0]);
        $this->assertContains('does_not_exist_anywhere', $logger->records[0][2]['functions']);
    }

    /**
     * Sanity check on the diffing logic itself: the bundle's own default configuration must
     * never trigger a false-positive warning.
     */
    public function testDoesNotWarnWhenEveryAllowListedNameIsRegistered(): void
    {
        $logger = new class extends AbstractLogger {
            /** @var list<array{0: string, 1: string, 2: array<string, mixed>}> */
            public array $records = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message, $context];
            }
        };

        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            $policy['tags'],
            $policy['filters'],
            $policy['functions'],
            [],
            $logger
        );
        $initializer->getEnvironment();

        $this->assertSame([], $logger->records);
    }

    private function generate(string $template, array $context): string
    {
        $policy = $this->getDefaultSandboxPolicy();

        $initializer = new SandboxExtensionInitializer(
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );

        $generator = new TemplateGenerator($initializer);

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
