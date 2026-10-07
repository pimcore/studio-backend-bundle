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

namespace Pimcore\Bundle\StudioBackendBundle\Twig;

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\InvalidTemplateException;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializerInterface;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\TwigOperatorEnvironmentProviderInterface;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Extension\SandboxExtension;
use function sprintf;
use function trigger_deprecation;

final class TemplateGenerator implements TemplateGeneratorInterface
{
    private readonly Environment $environment;

    private readonly SandboxExtension $sandboxExtension;

    /**
     * False for the isolated environment, whose sandbox is always enabled; true for the deprecated
     * fallback, which toggles the sandbox of the shared `twig` service around each render.
     */
    private readonly bool $togglesSandbox;

    public function __construct(
        Environment $twig,
        SandboxExtensionInitializerInterface $sandboxInitializer
    ) {
        $this->sandboxExtension = $sandboxInitializer->initialize();

        if ($sandboxInitializer instanceof TwigOperatorEnvironmentProviderInterface) {
            // Rendering must go through the isolated environment the sandbox belongs to, never
            // through the application's shared `twig` service.
            $this->environment = $sandboxInitializer->getEnvironment();
            $this->togglesSandbox = false;

            return;
        }

        trigger_deprecation(
            'pimcore/studio-backend-bundle',
            '2026.4',
            'Not implementing "%s" in "%s" is deprecated. Implement it so templates render in an isolated ' .
            'Twig environment; this becomes mandatory in the next major version.',
            TwigOperatorEnvironmentProviderInterface::class,
            $sandboxInitializer::class
        );
        $this->environment = $twig;
        $this->togglesSandbox = true;
    }

    /**
     * @throws InvalidTemplateException
     */
    public function generate(string $twigTemplate, array $arguments): string
    {
        if (!$this->togglesSandbox) {
            $this->assertEnvironmentIsSandboxed();

            return $this->render($twigTemplate, $arguments);
        }

        $this->assertSandboxIsRegisteredOnSharedEnvironment();
        $this->sandboxExtension->enableSandbox();

        try {
            return $this->render($twigTemplate, $arguments);
        } finally {
            $this->sandboxExtension->disableSandbox();
        }
    }

    /**
     * Fails closed when a provider returns an environment whose sandbox is missing or not enabled.
     *
     * @throws InvalidTemplateException
     */
    private function assertEnvironmentIsSandboxed(): void
    {
        if ($this->environment->hasExtension(SandboxExtension::class)
            && $this->environment->getExtension(SandboxExtension::class)->isSandboxed()
        ) {
            return;
        }

        throw new InvalidTemplateException(
            'The Twig environment provided for TwigOperator templates does not have its sandbox enabled.'
        );
    }

    /**
     * Fails closed when the sandbox the initializer returned is not the one registered on the environment that
     * renders the template, e.g. a decorator around the isolated initializer that does not forward
     * {@see TwigOperatorEnvironmentProviderInterface}.
     *
     * @throws InvalidTemplateException
     */
    private function assertSandboxIsRegisteredOnSharedEnvironment(): void
    {
        if ($this->environment->hasExtension(SandboxExtension::class)
            && $this->environment->getExtension(SandboxExtension::class) === $this->sandboxExtension
        ) {
            return;
        }

        throw new InvalidTemplateException(sprintf(
            'The TwigOperator sandbox is not registered on the rendering Twig environment. ' .
            'Implement "%s" in the sandbox initializer (or forward it from a decorator).',
            TwigOperatorEnvironmentProviderInterface::class
        ));
    }

    /**
     * @throws InvalidTemplateException
     */
    private function render(string $twigTemplate, array $arguments): string
    {
        try {
            return $this->environment->createTemplate($twigTemplate)->render($arguments);
        } catch (TwigError $e) {
            throw new InvalidTemplateException(
                sprintf(
                    'Invalid Twig template for TwigOperator: %s',
                    $e->getMessage()
                )
            );
        }
    }
}
