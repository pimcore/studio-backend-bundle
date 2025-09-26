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

use Pimcore\Bundle\StudioBackendBundle\Exception\Api\TransformerException;
use Pimcore\Bundle\StudioBackendBundle\Twig\Initializers\SandboxExtensionInitializerInterface;
use Throwable;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use function sprintf;

final class TemplateGenerator implements TemplateGeneratorInterface
{
    private readonly SandboxExtension $sandboxExtension;

    public function __construct(
        private readonly Environment $twig,
        SandboxExtensionInitializerInterface $sandboxInitializer
    ) {
        $this->sandboxExtension = $sandboxInitializer->initialize();
    }

    public function generate(string $twigTemplate, array $arguments): string
    {
        try {
            $this->sandboxExtension->enableSandbox();

            return $this->twig->createTemplate($twigTemplate)->render($arguments);
        } catch (Throwable $e) {
            throw new TransformerException(
                'TwigOperator',
                sprintf(
                    'Invalid Twig template for %s transformer: %s',
                    'twigOperator',
                    $e->getMessage()
                )
            );
        } finally {
            $this->sandboxExtension->disableSandbox();
        }
    }
}
