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
use Pimcore\Bundle\StudioBackendBundle\DependencyInjection\PimcoreStudioBackendExtension;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializerInterface;
use ReflectionMethod;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Regression test for GHSA-9g62-2rj4-v227: TemplateGeneratorTest only exercises
 * SandboxExtensionInitializer by constructing it directly and passing the four
 * object/function protection lists by hand, so it cannot see whether
 * PimcoreStudioBackendExtension::populateTwigSandboxExtension() still binds them to
 * core's own `pimcore.templating.twig.sandbox_security_policy.*` parameters - removing
 * one of those setArgument() calls would leave every other test green while the
 * deployed sandbox silently lost that protection. This loads the service definition
 * from the bundle's real config/twig.yaml and calls the real (private) wiring method,
 * so it fails if a binding is dropped or pointed at the wrong parameter.
 *
 * @internal
 */
final class SandboxSecurityPolicyWiringTest extends Unit
{
    private const array PARAMETER_FIXTURES = [
        '$blockedClasses' => [
            'parameter' => 'pimcore.templating.twig.sandbox_security_policy.blocked_classes',
            'value' => ['Pimcore\Model\User'],
        ],
        '$allowedClasses' => [
            'parameter' => 'pimcore.templating.twig.sandbox_security_policy.allowed_classes',
            'value' => ['Pimcore\Model\Asset'],
        ],
        '$blockedFunctions' => [
            'parameter' => 'pimcore.templating.twig.sandbox_security_policy.blocked_functions',
            'value' => ['pimcore_user'],
        ],
        '$hardBlockedMethods' => [
            'parameter' => 'pimcore.templating.twig.sandbox_security_policy.hard_blocked_methods',
            'value' => ['Pimcore\Model\User' => ['getPassword']],
        ],
    ];

    /**
     * @dataProvider argumentProvider
     */
    public function testBindsCoreSandboxSecurityPolicyParameter(string $argument, mixed $expectedValue): void
    {
        $container = $this->buildContainerWithSandboxWiring();
        $definition = $container->getDefinition(SandboxExtensionInitializerInterface::class);

        $this->assertSame(
            $expectedValue,
            $container->getParameterBag()->resolveValue($definition->getArgument($argument)),
        );
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function argumentProvider(): array
    {
        $cases = [];
        foreach (self::PARAMETER_FIXTURES as $argument => $fixture) {
            $cases[$argument] = [$argument, $fixture['value']];
        }

        return $cases;
    }

    /**
     * Loads the real service definition from config/twig.yaml, then invokes the real
     * (private) wiring method against it - the same two steps
     * PimcoreStudioBackendExtension::load() performs, minus the ~50 unrelated config
     * nodes the rest of load() also touches.
     */
    private function buildContainerWithSandboxWiring(): ContainerBuilder
    {
        $bundleRoot = dirname(__DIR__, 3);

        $container = new ContainerBuilder();
        $loader = new YamlFileLoader($container, new FileLocator($bundleRoot . '/config'));
        $loader->load('twig.yaml');

        foreach (self::PARAMETER_FIXTURES as $fixture) {
            $container->setParameter($fixture['parameter'], $fixture['value']);
        }

        $extension = new PimcoreStudioBackendExtension();
        $method = new ReflectionMethod($extension, 'populateTwigSandboxExtension');
        $method->setAccessible(true);
        $method->invoke($extension, [
            'twig' => [
                'sandbox_security_policy' => [
                    'tags' => ['if'],
                    'filters' => ['upper'],
                    'functions' => ['range'],
                ],
            ],
        ], $container);

        return $container;
    }
}
