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
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Extension\SandboxExtension;
use function sprintf;

/**
 * @internal
 */
final class TemplateGenerator implements TemplateGeneratorInterface
{
    private readonly Environment $environment;

    private readonly SandboxExtension $sandboxExtension;

    public function __construct(SandboxExtensionInitializerInterface $sandboxInitializer)
    {
        $this->sandboxExtension = $sandboxInitializer->initialize();
        // Rendering must go through the isolated environment the sandbox belongs to, never
        // through the application's shared `twig` service - see SandboxExtensionInitializer.
        $this->environment = $sandboxInitializer->getEnvironment();
    }

    public function generate(string $twigTemplate, array $arguments): string
    {
        $this->sandboxExtension->enableSandbox();

        try {
            return $this->environment->createTemplate($twigTemplate)->render($arguments);
        } catch (TwigError $e) {
            throw new InvalidTemplateException(
                sprintf(
                    'Invalid Twig template for TwigOperator: %s',
                    $e->getMessage()
                )
            );
        } finally {
            $this->sandboxExtension->disableSandbox();
        }
    }
}
