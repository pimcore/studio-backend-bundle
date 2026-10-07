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

namespace Pimcore\Bundle\StudioBackendBundle\Twig\Initializers;

use LogicException;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Psr\Log\LoggerInterface;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFunction;
use function abs;
use function array_diff;
use function array_filter;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function class_exists;
use function floor;
use function get_debug_type;
use function is_numeric;
use function is_string;
use function range;
use function sprintf;
use function str_starts_with;

/**
 * Builds a dedicated, isolated Twig environment for TwigOperator template rendering.
 *
 * - The environment is built from scratch (an {@see ArrayLoader} plus the formatting-only Twig Extra
 *   extensions) instead of reusing the application's shared `twig` service, so no Pimcore Twig function
 *   (`pimcore_object`, `pimcore_asset`, ...) exists here. A project that needs a further safe
 *   filter/function/tag tags its own Twig extension with
 *   {@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG}. Symfony also autoconfigures
 *   every Twig extension into the shared `twig` service unless the service sets `autoconfigure: false`.
 * - The sandbox is enabled for every template rendered through this environment, and its
 *   {@see SecurityPolicy} denies method and property access on every object (see {@see NoObjectAccessAllowed}).
 *   Core's `pimcore.templating.twig.sandbox_security_policy.*` class and method lists therefore do not apply
 *   here; core's blocked functions do.
 */
final class SandboxExtensionInitializer implements
    SandboxExtensionInitializerInterface,
    TwigOperatorEnvironmentProviderInterface
{
    /**
     * Switches {@see SecurityPolicy} into allowlist mode with nothing allowed: {@see NoObjectAccessAllowed}
     * is never instantiated, so every method and property access on an object is denied.
     *
     * @var list<class-string>
     */
    private const array ALLOWED_CLASSES = [
        NoObjectAccessAllowed::class,
    ];

    /**
     * Maximum number of elements the `range()` function returns. Best effort only: the `..` operator and
     * nested loops are not limited; `memory_limit` and `max_execution_time` remain the hard limits.
     * See {@see buildSafeRangeFunction()}.
     */
    private const int MAX_RANGE_SIZE = 1000;

    private Environment $environment;

    private SandboxExtension $sandboxExtension;

    /**
     * @param Environment $twig Unused since the environment is isolated (built from scratch here);
     *   kept only so the constructor stays backward compatible.
     * @param list<class-string> $blockedClasses Not applied: all object access is denied.
     * @param list<class-string> $allowedClasses Not applied: all object access is denied.
     * @param list<string> $blockedFunctions Core's blocked functions, added to every registered `pimcore_*` one.
     * @param array<class-string, list<string>> $hardBlockedMethods Not applied: all object access is denied.
     * @param iterable<mixed> $additionalExtensions Every service tagged
     *   {@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG}, registered
     *   into the isolated environment. This is the supported extension point for a project
     *   that needs an additional SAFE filter/function/tag beyond the built-in allow-list.
     *   Typed as `mixed` deliberately: the DI tag cannot itself guarantee every tagged
     *   service implements {@see ExtensionInterface}, which is exactly why
     *   {@see registerAdditionalExtensions()} checks it at runtime instead of trusting it.
     */
    public function __construct(
        Environment $twig,
        private readonly array $allowedTags,
        private readonly array $allowedFilters,
        private readonly array $allowedFunctions,
        array $blockedClasses = [],
        array $allowedClasses = [],
        private readonly array $blockedFunctions = [],
        array $hardBlockedMethods = [],
        private readonly iterable $additionalExtensions = [],
        private readonly ?LoggerInterface $logger = null
    ) {
        // Kept for backward compatibility only, see the parameter docs above.
        unset($twig, $blockedClasses, $allowedClasses, $hardBlockedMethods);
    }

    public function initialize(): SandboxExtension
    {
        $this->build();

        return $this->sandboxExtension;
    }

    public function getEnvironment(): Environment
    {
        $this->build();

        return $this->environment;
    }

    private function build(): void
    {
        if (isset($this->environment, $this->sandboxExtension)) {
            return;
        }

        $environment = new Environment(new ArrayLoader());

        if (class_exists(StringExtension::class)) {
            $environment->addExtension(new StringExtension());
        }

        if (class_exists(IntlExtension::class)) {
            $environment->addExtension(new IntlExtension());
        }

        $this->registerAdditionalExtensions($environment);
        // Overrides Twig core's uncapped `range` - see MAX_RANGE_SIZE. Registered via
        // addFunction() (staging), which Twig always applies after every addExtension() call
        // regardless of registration order, so no additional extension above can reopen this.
        $environment->addFunction($this->buildSafeRangeFunction());

        $policy = $this->buildSecurityPolicy();
        $sandbox = new SandboxExtension($policy, true);
        $environment->addExtension($sandbox);

        // Environment::getFunctions() finalizes (locks) the extension set as a side effect,
        // so the dynamic pimcore_* lookup can only run after every addExtension()/addFunction()
        // call above - otherwise those registrations themselves would fail. The policy is
        // updated in place; SandboxExtension keeps the same $policy instance internally.
        $policy->setBlockedFunctions(array_values(array_unique([
            ...$this->blockedFunctions,
            ...$this->blockedPimcoreFunctions($environment),
        ])));
        $this->warnAboutUnregisteredAllowListNames($environment);

        $this->environment = $environment;
        $this->sandboxExtension = $sandbox;
    }

    private function buildSecurityPolicy(): SecurityPolicy
    {
        return new SecurityPolicy(
            $this->allowedTags,
            $this->allowedFilters,
            $this->allowedFunctions,
            [],
            self::ALLOWED_CLASSES,
            $this->blockedFunctions,
            []
        );
    }

    /**
     * @throws LogicException if a tagged service does not implement ExtensionInterface
     */
    private function registerAdditionalExtensions(Environment $environment): void
    {
        foreach ($this->additionalExtensions as $extension) {
            if (!$extension instanceof ExtensionInterface) {
                throw new LogicException(sprintf(
                    'Every service tagged "%s" must implement %s, got "%s".',
                    TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG,
                    ExtensionInterface::class,
                    get_debug_type($extension)
                ));
            }

            $environment->addExtension($extension);
        }
    }

    private function buildSafeRangeFunction(): TwigFunction
    {
        return new TwigFunction('range', static function (
            int|float|string $low,
            int|float|string $high,
            int|float $step = 1
        ): array {
            self::assertRangeIsBounded($low, $high, $step);

            return range($low, $high, $step);
        });
    }

    /**
     * Pre-computes the resulting element count for a numeric span and rejects it before the
     * real `range()` call, instead of letting PHP materialize the array first and counting it
     * afterwards - the latter would already have paid the allocation cost the cap exists to
     * avoid. A character range (both bounds non-numeric strings) is skipped: it is inherently bounded
     * to at most the codepoint distance between the two characters. A mixed range such as
     * `range('a', 1000000)` is not: PHP treats the non-numeric bound as 0.
     *
     * @throws RuntimeError if the span would exceed MAX_RANGE_SIZE
     */
    private static function assertRangeIsBounded(int|float|string $low, int|float|string $high, int|float $step): void
    {
        if (self::isNonNumericString($low) && self::isNonNumericString($high)) {
            return;
        }

        // A step of 0 is left to PHP, which rejects it itself.
        if ($step == 0) {
            return;
        }

        // PHP treats a non-numeric bound as 0 when the other bound is numeric.
        $span = floor(abs((self::toNumber($high) - self::toNumber($low)) / (float) $step)) + 1;

        if ($span > self::MAX_RANGE_SIZE) {
            throw new RuntimeError(sprintf(
                'range(%s, %s, %s) would generate more than %d elements, which is not allowed ' .
                'in a TwigOperator template.',
                $low,
                $high,
                $step,
                self::MAX_RANGE_SIZE
            ));
        }
    }

    private static function isNonNumericString(int|float|string $value): bool
    {
        return is_string($value) && !is_numeric($value);
    }

    private static function toNumber(int|float|string $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Blocks every `pimcore_*` function actually registered on this isolated environment.
     * In practice this is always an empty list - no Pimcore Twig extension is registered
     * here - but computing it instead of hardcoding `[]` means a future change that
     * accidentally adds one does not silently reopen the element/service loader hole: the
     * blanket `pimcore_*` prefix auto-allow in {@see SecurityPolicy::checkSecurity()} would
     * otherwise let it straight through.
     *
     * @return list<string>
     */
    private function blockedPimcoreFunctions(Environment $environment): array
    {
        return array_values(array_filter(
            array_keys($environment->getFunctions()),
            static fn (string $name): bool => str_starts_with($name, 'pimcore_')
        ));
    }

    /**
     * A configured `sandbox_security_policy` tag/filter/function name only takes effect if a
     * Twig extension in this isolated environment actually registers it - the app's shared
     * `twig` service and its extensions are never reachable here (see the class docblock).
     * Adding a name to the allow-list without also registering a matching extension via
     * {@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG} is a
     * configuration mistake that otherwise fails silently until a template actually uses the
     * name - logged once here, at build time, instead.
     */
    private function warnAboutUnregisteredAllowListNames(Environment $environment): void
    {
        if ($this->logger === null) {
            return;
        }

        $unregistered = array_filter([
            'tags' => array_diff($this->allowedTags, $this->registeredTagNames($environment)),
            'filters' => array_diff($this->allowedFilters, array_keys($environment->getFilters())),
            'functions' => array_diff($this->allowedFunctions, array_keys($environment->getFunctions())),
        ]);

        if ($unregistered === []) {
            return;
        }

        $this->logger->warning(
            'TwigOperator sandbox_security_policy allow-lists one or more tag/filter/function ' .
            'names that no Twig extension in the isolated environment registers - templates ' .
            'using them will fail at render time even though the name is allow-listed. Register ' .
            'a matching Twig extension via the "' .
            TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG .
            '" service tag, or remove the name from the configuration.',
            $unregistered
        );
    }

    /**
     * @return list<string>
     */
    private function registeredTagNames(Environment $environment): array
    {
        return array_map(
            static fn (TokenParserInterface $parser): string => $parser->getTag(),
            $environment->getTokenParsers()
        );
    }
}
