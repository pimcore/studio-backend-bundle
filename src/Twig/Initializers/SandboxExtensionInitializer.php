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

use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use LogicException;
use PDO;
use PDOStatement;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
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
 * Two things distinguish this from a "normal" sandbox setup, both deliberate:
 *
 * 1. The environment is built from scratch here (an {@see ArrayLoader}, plus the two
 *    formatting-only Twig Extra extensions) instead of reusing the application's shared
 *    `twig` service. That shared environment carries every Pimcore Twig extension
 *    (`pimcore_object`, `pimcore_asset`, `pimcore_document`, ...) - none of those functions
 *    are registered here, so they are not merely sandboxed, they do not exist for this
 *    environment to resolve. TwigOperator templates have no legitimate use for an
 *    element/service loader; formatting a value never needs one. A project that needs an
 *    additional SAFE filter/function/tag registers its own Twig extension under the
 *    {@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG} service tag
 *    instead - it is added to this isolated environment, never to the shared one.
 * 2. The {@see SecurityPolicy} is built with all seven constructor arguments and attached to
 *    a SandboxExtension instance that belongs only to this isolated environment.
 *    Previously this initializer fetched the application's shared SandboxExtension
 *    (`$twig->getExtension(SandboxExtension::class)`) and replaced its policy in place -
 *    which both left method/property access on every object unrestricted (blockedClasses/
 *    allowedClasses/blockedFunctions/hardBlockedMethods were never populated) and
 *    permanently overwrote the policy core itself uses for its own sandboxed Twig
 *    rendering (Mailer, the "Text" layout component, ...) for the remainder of the process.
 *    `allowedClasses` is populated too (see {@see NoObjectAccessAllowed}), switching the
 *    policy into allowlist mode: even an object that reaches the template unsanitized has
 *    every method/property access denied, regardless of the {@see BLOCKED_CLASSES}
 *    enumeration below.
 */
final class SandboxExtensionInitializer implements
    SandboxExtensionInitializerInterface,
    TwigOperatorEnvironmentProviderInterface
{
    /**
     * FQCNs that must never be traversable (method calls or property access) from a
     * TwigOperator template. The first block mirrors Pimcore core's own sandbox denylist
     * (`pimcore.templating.twig.sandbox_security_policy.blocked_classes`): the
     * persistence/infrastructure layer and the admin user model. The second block is
     * specific to TwigOperator: it has no legitimate use for any Pimcore element getter,
     * let alone save()/delete(), so every element type is blocked wholesale rather than
     * enumerating individual methods.
     *
     * Kept and populated even though {@see ALLOWED_CLASSES} switches the policy into
     * allowlist mode (where {@see SecurityPolicy} ignores blockedClasses entirely): it
     * documents intent, and it is what actually protects the sandbox the moment anyone ever
     * adds a legitimate class to the allowlist in the future.
     *
     * @var list<class-string>
     */
    private const array BLOCKED_CLASSES = [
        AbstractDao::class,
        Connection::class,
        PDO::class,
        PDOStatement::class,
        ContainerInterface::class,
        PsrContainerInterface::class,
        Process::class,
        User::class,
        ElementInterface::class,
    ];

    /**
     * Switches {@see SecurityPolicy} into allowlist mode: once non-empty, every object that
     * is not an instance of one of these classes has ALL method/property access denied,
     * unconditionally. {@see NoObjectAccessAllowed} is never instantiated, so this denies
     * every real object - see its docblock for why that is the point.
     *
     * @var list<class-string>
     */
    private const array ALLOWED_CLASSES = [
        NoObjectAccessAllowed::class,
    ];

    /**
     * Per-class method denylist, enforced regardless of {@see BLOCKED_CLASSES}/allowlist
     * mode. Defense in depth for value types that do legitimately reach a TwigOperator
     * template unblocked - e.g. a DateTime instance for a date/datetime column - so that
     * only read-only formatting (format(), diff(), ...) remains reachable, never mutation.
     *
     * @var array<class-string, list<string>>
     */
    private const array HARD_BLOCKED_METHODS = [
        DateTime::class => ['modify', 'setDate', 'setISODate', 'setTime', 'setTimestamp', 'setTimezone', 'add', 'sub'],
        DateTimeImmutable::class => [
            'modify', 'setDate', 'setISODate', 'setTime', 'setTimestamp', 'setTimezone', 'add', 'sub',
        ],
    ];

    /**
     * Maximum number of elements the isolated environment's `range()` allows. `range()` maps
     * directly onto PHP's own `range()` (see Twig's CoreExtension), which materializes the
     * whole result array immediately - an attacker-controlled span reachable straight from
     * template text (`range(0, 100000000)`) would otherwise allocate a huge array with no
     * object or method call involved at all. See {@see buildSafeRangeFunction()}.
     */
    private const int MAX_RANGE_SIZE = 1000;

    private Environment $environment;

    private SandboxExtension $sandboxExtension;

    /**
     * @param Environment $twig Unused since the environment is isolated (built from scratch here);
     *   kept only so the constructor stays backward compatible.
     * @param iterable<mixed> $additionalExtensions Every service tagged
     *   {@see TwigOperatorEnvironmentProviderInterface::TWIG_OPERATOR_EXTENSION_TAG}, registered
     *   into the isolated environment. This is the supported extension point for a project
     *   that needs an additional SAFE filter/function/tag beyond the built-in allow-list.
     *   Typed as `mixed` deliberately: the DI tag cannot itself guarantee every tagged
     *   service implements {@see ExtensionInterface}, which is exactly why
     *   {@see registerAdditionalExtensions()} checks it at runtime instead of trusting it.
     */
    // @phpstan-ignore constructor.unusedParameter
    public function __construct(
        Environment $twig,
        private readonly array $allowedTags,
        private readonly array $allowedFilters,
        private readonly array $allowedFunctions,
        private readonly iterable $additionalExtensions = [],
        private readonly ?LoggerInterface $logger = null
    ) {
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
        $sandbox = new SandboxExtension($policy);
        $environment->addExtension($sandbox);

        // Environment::getFunctions() finalizes (locks) the extension set as a side effect,
        // so the dynamic pimcore_* lookup can only run after every addExtension()/addFunction()
        // call above - otherwise those registrations themselves would fail. The policy is
        // updated in place; SandboxExtension keeps the same $policy instance internally.
        $policy->setBlockedFunctions($this->blockedPimcoreFunctions($environment));
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
            self::BLOCKED_CLASSES,
            self::ALLOWED_CLASSES,
            [],
            self::HARD_BLOCKED_METHODS
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
