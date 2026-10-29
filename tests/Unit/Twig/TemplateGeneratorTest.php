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

use const E_USER_DEPRECATED;
use Codeception\Test\Unit;
use DateTime;
use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidTemplateException;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializer;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializerInterface;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\TwigOperatorEnvironmentProviderInterface;
use Pimcore\Bundle\StudioBackendBundle\Twig\TemplateGenerator;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Psr\Log\AbstractLogger;
use Stringable;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityError;
use Twig\TwigFunction;
use function array_merge;
use function restore_error_handler;
use function set_error_handler;
use function str_contains;
use function strtoupper;

/**
 * @internal
 */
final class TemplateGeneratorTest extends Unit
{
    use DefaultSandboxPolicyTrait;

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
     * Pimcore functions registered on the application's `twig` service are not reachable: the template renders in
     * the isolated environment, where they do not exist, and the shared ones are never called.
     *
     * @dataProvider pimcoreFunctionProvider
     */
    public function testPimcoreFunctionsOfTheSharedEnvironmentAreUnreachable(string $call): void
    {
        $calls = 0;
        $shared = new Environment(new ArrayLoader());
        $names = ['pimcore_object', 'pimcore_object_by_path', 'pimcore_asset', 'pimcore_document', 'pimcore_user'];
        foreach ($names as $name) {
            $shared->addFunction(new TwigFunction($name, static function () use (&$calls): string {
                ++$calls;

                return 'loaded';
            }));
        }
        $policy = $this->getDefaultSandboxPolicy();
        $generator = new TemplateGenerator(
            $shared,
            new SandboxExtensionInitializer($shared, $policy['tags'], $policy['filters'], $policy['functions'])
        );

        try {
            $generator->generate('{{ ' . $call . ' }}', []);
            self::fail('The function must not be callable.');
        } catch (InvalidTemplateException $exception) {
            $this->assertStringContainsString('Unknown "pimcore_', $exception->getMessage());
        }

        $this->assertSame(0, $calls);
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
     * Every method on an object is denied, read-only ones included; dates are formatted with filters.
     *
     * @dataProvider dateMethodProvider
     */
    public function testDeniesEveryMethodOnADateObject(string $template): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate($template, ['value' => new DateTime('2020-01-01')]);
    }

    public static function dateMethodProvider(): iterable
    {
        yield 'mutating' => ["{{ value.modify('+1 day')|date('Y') }}"];
        yield 'read-only' => ["{{ value.format('Y') }}"];
    }

    /**
     * A Pimcore element reaching the template has every method call denied before the call reaches it.
     */
    public function testBlocksMethodCallOnElementInterfaceValue(): void
    {
        $deleted = false;
        $element = $this->makeEmpty(ElementInterface::class, [
            'delete' => function () use (&$deleted): void {
                $deleted = true;
            },
        ]);

        try {
            $this->generate('{{ value.delete() }}', ['value' => $element]);
            self::fail('The method call must be rejected.');
        } catch (InvalidTemplateException $exception) {
            $this->assertStringContainsString('is not allowed', $exception->getMessage());
        }

        $this->assertFalse($deleted, 'delete() must never be invoked from a sandboxed template.');
    }

    public function testRangeFunctionWithinTheCapWorks(): void
    {
        $this->assertSame('1,2,3,4,5', $this->generate('{{ range(1, 5)|join(",") }}', []));
    }

    public function testRangeFunctionAtExactlyTheCapWorks(): void
    {
        $this->assertSame('1000', $this->generate('{{ range(1, 1000)|length }}', []));
    }

    public function testRangeFunctionWithNonUnitStepAtTheCapWorks(): void
    {
        $this->assertSame('1000', $this->generate('{{ range(0, 1999, 2)|length }}', []));
    }

    /**
     * range() rejects a span beyond the cap before PHP allocates the array.
     */
    public function testRangeFunctionRejectsASpanBeyondTheCap(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->expectExceptionMessage('would generate more than 1000 elements');
        $this->generate('{{ range(0, 1000000)|length }}', []);
    }

    /**
     * Large integer bounds are compared as integers; as floats they would round to the same value.
     */
    public function testRangeFunctionRejectsALargeIntegerSpanBeyondTheCap(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->expectExceptionMessage('would generate more than 1000 elements');
        $this->generate('{{ range(4611686018427388428, 4611686018427389428)|length }}', []);
    }

    /**
     * PHP counts 1001 elements for range(0, 1100, 1.1); the estimate must not fall one short because of float drift.
     */
    public function testRangeFunctionRejectsAFloatSpanJustBeyondTheCap(): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->expectExceptionMessage('would generate more than 1000 elements');
        $this->generate('{{ range(0, 1100, 1.1)|length }}', []);
    }

    /**
     * An undefined bound is null and counts as 0, like Twig's own range().
     */
    public function testRangeFunctionTreatsAnUndefinedBoundAsZero(): void
    {
        $this->assertSame('0,1,2,3', $this->generate('{{ range(value.missing, 3)|join(",") }}', ['value' => []]));
    }

    /**
     * The capped `range` keeps the parameter names of PHP's range(), so named arguments still work.
     */
    public function testRangeFunctionSupportsNamedArguments(): void
    {
        $this->assertSame('1,3,5', $this->generate('{{ range(start=1, end=5, step=2)|join(",") }}', []));
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
     * The 2026.x constructor shapes must keep working.
     */
    public function testOldConstructorSignaturesStillWork(): void
    {
        $policy = $this->getDefaultSandboxPolicy();
        $twig = new Environment(new ArrayLoader());
        $initializer = new SandboxExtensionInitializer(
            $twig,
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );
        $generator = new TemplateGenerator($twig, $initializer);

        $this->assertSame('HI', $generator->generate('{{ value|upper }}', ['value' => 'hi']));
        $this->assertNotSame($twig, $initializer->getEnvironment());
    }

    /**
     * A decorator that does not forward {@see TwigOperatorEnvironmentProviderInterface} returns the isolated
     * sandbox, which is not registered on the shared environment: rendering must fail instead of running
     * the template without a sandbox.
     */
    public function testDecoratorWithoutProviderInterfaceFailsClosed(): void
    {
        $policy = $this->getDefaultSandboxPolicy();
        $inner = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );
        $decorator = new class($inner) implements SandboxExtensionInitializerInterface {
            public function __construct(private readonly SandboxExtensionInitializerInterface $inner)
            {
            }

            public function initialize(): SandboxExtension
            {
                return $this->inner->initialize();
            }
        };
        $shared = new Environment(new ArrayLoader());
        $shared->addExtension(new SandboxExtension(new SecurityPolicy()));

        set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);

        try {
            $generator = new TemplateGenerator($shared, $decorator);
        } finally {
            restore_error_handler();
        }

        $this->expectException(InvalidTemplateException::class);
        $this->expectExceptionMessage(TwigOperatorEnvironmentProviderInterface::class);
        $generator->generate('{{ value|upper }}', ['value' => 'hi']);
    }

    /**
     * The isolated environment is sandboxed on its own, also for callers rendering through it directly.
     */
    public function testIsolatedEnvironmentIsSandboxedWithoutToggling(): void
    {
        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );

        $this->expectException(SecurityError::class);
        $initializer->getEnvironment()->createTemplate('{% include "other" %}')->render([]);
    }

    /**
     * Rendering through the isolated environment does not toggle the sandbox, so Twig 3.29's
     * enableSandbox()/disableSandbox() deprecations are not triggered.
     */
    public function testRenderingTriggersNoDeprecation(): void
    {
        $deprecations = [];
        set_error_handler(static function (int $no, string $message) use (&$deprecations): bool {
            if (str_contains($message, 'Sandbox()')) {
                $deprecations[] = $message;
            }

            return true;
        }, E_USER_DEPRECATED);

        try {
            $this->generate('{{ value|upper }}', ['value' => 'hi']);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
    }

    /**
     * A custom initializer without the provider interface falls back to the injected
     * environment and emits a deprecation.
     */
    public function testCustomInitializerWithoutProviderInterfaceFallsBackWithDeprecation(): void
    {
        $twig = new Environment(new ArrayLoader());
        $sandbox = new SandboxExtension(new SecurityPolicy(['if'], ['upper', 'escape'], []));
        $twig->addExtension($sandbox);
        $custom = new class($sandbox) implements SandboxExtensionInitializerInterface {
            public function __construct(private readonly SandboxExtension $sandbox)
            {
            }

            public function initialize(): SandboxExtension
            {
                return $this->sandbox;
            }
        };

        $deprecations = [];
        set_error_handler(static function (int $no, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            $generator = new TemplateGenerator($twig, $custom);
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString(TwigOperatorEnvironmentProviderInterface::class, $deprecations[0]);
        $this->assertSame('HI', $generator->generate('{{ value|upper }}', ['value' => 'hi']));

        set_error_handler(static fn (): bool => true, E_USER_DEPRECATED);

        try {
            $this->expectException(InvalidTemplateException::class);
            $generator->generate('{{ value|lower }}', ['value' => 'HI']);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * A provider whose environment is not sandboxed must not render.
     */
    public function testProviderWithoutEnabledSandboxFailsClosed(): void
    {
        $environment = new Environment(new ArrayLoader());
        $sandbox = new SandboxExtension(new SecurityPolicy(['if'], ['upper'], []));
        $environment->addExtension($sandbox);
        $provider = new class($environment, $sandbox) implements
            SandboxExtensionInitializerInterface,
            TwigOperatorEnvironmentProviderInterface {
            public function __construct(
                private readonly Environment $environment,
                private readonly SandboxExtension $sandbox
            ) {
            }

            public function initialize(): SandboxExtension
            {
                return $this->sandbox;
            }

            public function getEnvironment(): Environment
            {
                return $this->environment;
            }
        };

        $generator = new TemplateGenerator(new Environment(new ArrayLoader()), $provider);

        $this->expectException(InvalidTemplateException::class);
        $generator->generate('{{ value|upper }}', ['value' => 'hi']);
    }

    public function testProviderInitializerIsUsedWithoutDeprecation(): void
    {
        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );

        $deprecations = [];
        set_error_handler(static function (int $no, string $message) use (&$deprecations): bool {
            $deprecations[] = $message;

            return true;
        }, E_USER_DEPRECATED);

        try {
            new TemplateGenerator(new Environment(new ArrayLoader()), $initializer);
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $deprecations);
        $this->assertInstanceOf(TwigOperatorEnvironmentProviderInterface::class, $initializer);
    }

    /**
     * @dataProvider mixedRangeProvider
     */
    public function testRangeFunctionRejectsMixedBoundsBeyondTheCap(string $call): void
    {
        $this->expectException(InvalidTemplateException::class);
        $this->generate('{{ ' . $call . '|length }}', []);
    }

    public function mixedRangeProvider(): iterable
    {
        yield 'non-numeric low' => ['range("a", 1000000)'];
        yield 'non-numeric high' => ['range(1000000, "a")'];
        yield 'float step tiny' => ['range(0, 10, 0.000001)'];
    }

    /**
     * The application's `twig` service passed to both constructors is never used for rendering, and its
     * SandboxExtension policy stays untouched.
     */
    public function testGeneratorNeverTouchesAnExternalSandboxExtension(): void
    {
        $externalPolicy = new SecurityPolicy(['set'], ['escape', 'trans', 'default'], ['path', 'asset']);
        $externalSandbox = new SandboxExtension($externalPolicy);
        $externalTwig = new Environment(new ArrayLoader());
        $externalTwig->addExtension($externalSandbox);

        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            $externalTwig,
            $policy['tags'],
            $policy['filters'],
            $policy['functions']
        );
        $generator = new TemplateGenerator($externalTwig, $initializer);

        $rendered = $generator->generate('{{ value|upper }}', ['value' => 'twig-operator-render']);
        $this->assertSame('TWIG-OPERATOR-RENDER', $rendered);

        try {
            $generator->generate('{{ pimcore_object(1) }}', []);
            self::fail('pimcore_object() must not be available.');
        } catch (InvalidTemplateException $exception) {
            $this->assertStringContainsString('Unknown "pimcore_object" function', $exception->getMessage());
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
     * The DI extension point ({@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG})
     * is the supported way to add a project-defined filter/function/tag: the isolated
     * environment never sees the application's shared `twig` service, so a tagged extension is
     * registered directly onto it.
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
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['project_shout']),
            additionalExtensions: [$extension]
        );
        $generator = new TemplateGenerator(new Environment(new ArrayLoader()), $initializer);

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
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['does_not_exist_anywhere']),
            logger: $logger
        );

        // build() memoizes; calling twice must still log only once.
        $initializer->getEnvironment();
        $initializer->getEnvironment();

        $this->assertCount(1, $logger->records, 'The warning must be logged exactly once.');
        $this->assertSame('warning', $logger->records[0][0]);
        $this->assertContains('does_not_exist_anywhere', $logger->records[0][2]['functions']);
    }

    /**
     * Allow-listed `pimcore_*` names are ignored, and the warning says so instead of asking for an extension.
     */
    public function testWarnsAboutIgnoredPimcoreFunctionNames(): void
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
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['pimcore_object']),
            logger: $logger
        );
        $initializer->getEnvironment();

        $this->assertCount(1, $logger->records);
        $this->assertSame(['ignored_functions' => ['pimcore_object']], $logger->records[0][2]);
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
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions'],
            logger: $logger
        );
        $initializer->getEnvironment();

        $this->assertSame([], $logger->records);
    }

    /**
     * Regression test for GHSA-9g62-2rj4-v227, extended: core's class and method lists can neither open nor
     * widen object access. The "allowed" cases would render if core's allowed classes were applied.
     *
     * @dataProvider coreClassListProvider
     */
    public function testCoreClassListsDoNotOpenObjectAccess(
        bool $blocked,
        bool $allowed,
        bool $hardBlocked
    ): void {
        $fixture = new class {
            public function getSecret(): string
            {
                return 'super-secret';
            }
        };

        $this->expectException(InvalidTemplateException::class);
        $this->generate(
            '{{ value.getSecret() }}',
            ['value' => $fixture],
            blockedClasses: $blocked ? [$fixture::class] : [],
            allowedClasses: $allowed ? [$fixture::class] : [],
            hardBlockedMethods: $hardBlocked ? [$fixture::class => ['getSecret']] : []
        );
    }

    public static function coreClassListProvider(): iterable
    {
        yield 'no lists' => [false, false, false];
        yield 'blocked class' => [true, false, false];
        yield 'allowed class' => [false, true, false];
        yield 'hard-blocked method' => [false, false, true];
        yield 'allowed class with hard-blocked method' => [false, true, true];
    }

    /**
     * Allowed functions win over blocked ones in the policy, so allow-listing a registered `pimcore_*`
     * function must not make it callable.
     */
    public function testAllowListedPimcoreFunctionStaysBlocked(): void
    {
        $extension = new class extends AbstractExtension {
            public function getFunctions(): array
            {
                return [new TwigFunction('pimcore_test_lookup', static fn (): string => 'secret')];
            }
        };
        $policy = $this->getDefaultSandboxPolicy();
        $initializer = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            array_merge($policy['functions'], ['pimcore_test_lookup']),
            additionalExtensions: [$extension]
        );
        $generator = new TemplateGenerator(new Environment(new ArrayLoader()), $initializer);

        $this->expectException(InvalidTemplateException::class);
        $generator->generate('{{ pimcore_test_lookup() }}', []);
    }

    /**
     * Regression test for GHSA-9g62-2rj4-v227: a `pimcore_*` function stays blocked, also when a
     * project registers it into the isolated environment.
     */
    public function testBlocksBlockedPimcoreFunction(): void
    {
        $extension = new class extends AbstractExtension {
            public function getFunctions(): array
            {
                return [new TwigFunction('pimcore_test_lookup', static fn (): string => 'secret')];
            }
        };

        $this->expectException(InvalidTemplateException::class);
        $this->generate(
            '{{ pimcore_test_lookup() }}',
            [],
            blockedFunctions: ['pimcore_test_lookup'],
            additionalExtensions: [$extension]
        );
    }

    private function generate(
        string $template,
        array $context,
        array $blockedClasses = [],
        array $allowedClasses = [],
        array $blockedFunctions = [],
        array $hardBlockedMethods = [],
        iterable $additionalExtensions = []
    ): string {
        $policy = $this->getDefaultSandboxPolicy();

        $initializer = new SandboxExtensionInitializer(
            new Environment(new ArrayLoader()),
            $policy['tags'],
            $policy['filters'],
            $policy['functions'],
            $blockedClasses,
            $allowedClasses,
            $blockedFunctions,
            $hardBlockedMethods,
            $additionalExtensions
        );

        $generator = new TemplateGenerator(new Environment(new ArrayLoader()), $initializer);

        return $generator->generate($template, $context);
    }
}
