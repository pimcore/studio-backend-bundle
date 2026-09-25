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
use PDO;
use PDOStatement;
use Pimcore\Model\Dao\AbstractDao;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\User;
use Pimcore\Twig\Sandbox\SecurityPolicy;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Process;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\ArrayLoader;
use function array_filter;
use function array_keys;
use function array_values;
use function class_exists;
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
 *    element/service loader; formatting a value never needs one.
 * 2. The {@see SecurityPolicy} is built with all seven constructor arguments and attached to
 *    a SandboxExtension instance that belongs only to this isolated environment.
 *    Previously this initializer fetched the application's shared SandboxExtension
 *    (`$twig->getExtension(SandboxExtension::class)`) and replaced its policy in place -
 *    which both left method/property access on every object unrestricted (blockedClasses/
 *    allowedClasses/blockedFunctions/hardBlockedMethods were never populated) and
 *    permanently overwrote the policy core itself uses for its own sandboxed Twig
 *    rendering (Mailer, the "Text" layout component, ...) for the remainder of the process.
 *
 * @internal
 */
final class SandboxExtensionInitializer implements SandboxExtensionInitializerInterface
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

    private Environment $environment;

    private SandboxExtension $sandboxExtension;

    public function __construct(
        private readonly array $allowedTags,
        private readonly array $allowedFilters,
        private readonly array $allowedFunctions
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

        $policy = $this->buildSecurityPolicy();
        $sandbox = new SandboxExtension($policy);
        $environment->addExtension($sandbox);

        // Environment::getFunctions() finalizes (locks) the extension set as a side effect,
        // so the dynamic pimcore_* lookup can only run after every addExtension() call above -
        // otherwise the SandboxExtension registration itself would fail. The policy is
        // updated in place; SandboxExtension keeps the same $policy instance internally.
        $policy->setBlockedFunctions($this->blockedPimcoreFunctions($environment));

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
            [],
            [],
            self::HARD_BLOCKED_METHODS
        );
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
}
